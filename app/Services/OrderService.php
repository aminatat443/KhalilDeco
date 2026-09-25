<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Models\Coupon;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Throwable;

class OrderService
{
    public function __construct(
        private readonly CartService $cart,
        private readonly PromotionService $promotions,
    ) {
    }

    /**
     * Crée la commande à partir du panier courant (checkout en 5 étapes, section 34 du cahier des charges).
     * Ne décrémente pas le stock ici — cela se fait uniquement à la confirmation (docs/SPEC.md §2.3/§2.4),
     * un article au panier n'est jamais réservé.
     */
    /**
     * $deliveryZone est la SEULE source du tarif et du nom de zone appliqués (Configuration →
     * Zones et tarifs de livraison) — jamais un montant recalculé ou codé ailleurs. Son nom et
     * son tarif sont figés sur la commande au moment de la création (delivery_zone/delivery_fee)
     * : une modification ultérieure de la zone n'affecte jamais les commandes déjà passées.
     */
    public function createFromCart(array $customer, array $delivery, string $paymentMethod, Delivery $deliveryZone, ?Coupon $coupon = null): Order
    {
        $items = $this->cart->items();

        if (empty($items)) {
            throw new RuntimeException('Le panier est vide.');
        }

        $subtotal = array_sum(array_column($items, 'subtotal'));
        $discount = $coupon ? $this->promotions->couponDiscount($coupon, $subtotal) : 0;
        $deliveryFee = $deliveryZone->fee;

        return DB::transaction(function () use ($items, $customer, $delivery, $paymentMethod, $deliveryZone, $deliveryFee, $coupon, $subtotal, $discount) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $customer['user_id'] ?? null,
                'address_id' => $delivery['address_id'] ?? null,
                'coupon_id' => $coupon?->id,
                'delivery_id' => $deliveryZone->id,
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'customer_email' => $customer['email'],
                'delivery_region' => $delivery['region'],
                'delivery_zone' => $deliveryZone->zone,
                'delivery_city' => $delivery['city'],
                'delivery_quartier' => $delivery['quartier'] ?? null,
                'delivery_address' => $delivery['address'],
                'delivery_instructions' => $delivery['instructions'] ?? null,
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'discount' => $discount,
                'total' => max(0, $subtotal + $deliveryFee - $discount),
                'payment_method' => $paymentMethod,
                'payment_status' => 'pending',
                // Paiement en ligne : la commande reste "en_attente_paiement" — jamais "recue",
                // qui laisserait croire à l'équipe qu'elle est prête à traiter — tant que le
                // paiement n'est pas confirmé par le serveur (voir confirm(), appelé uniquement
                // après vérification webhook/IPN). Le paiement à la livraison, jamais bloquant,
                // reste "recue" dès la création comme avant.
                'status' => $paymentMethod === 'cod' ? 'recue' : 'en_attente_paiement',
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'product_variant_id' => $item['variant']?->id,
                    'product_name' => $item['product']->name,
                    'variant_label' => $this->variantLabel($item['variant']),
                    'sku' => $item['variant']?->sku,
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal'],
                ]);
            }

            if ($coupon) {
                $coupon->usages()->create([
                    'order_id' => $order->id,
                    'user_id' => $customer['user_id'] ?? null,
                ]);
            }

            $this->cart->clear();

            return $order;
        });
    }

    /**
     * Enregistre une vente conclue directement en boutique : le client repart avec l'article
     * en main, payé en espèces, donc la commande est immédiatement confirmée (le stock est
     * décrémenté tout de suite, pas d'étape d'attente comme pour une commande en ligne).
     *
     * @param  array<int, array{product_id: int, variant_id: ?int, quantity: int}>  $items
     */
    public function createInStore(array $customer, array $items): Order
    {
        if (empty($items)) {
            throw new RuntimeException('Ajoutez au moins un article.');
        }

        return DB::transaction(function () use ($customer, $items) {
            $lines = [];
            $subtotal = 0;

            foreach ($items as $line) {
                $product = Product::findOrFail($line['product_id']);
                $variant = ($line['variant_id'] ?? null) ? ProductVariant::with('attributeValues.attribute')->findOrFail($line['variant_id']) : null;
                $quantity = max(1, (int) $line['quantity']);
                $unitPrice = $this->promotions->effectivePrice($product, $variant);

                $lines[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $quantity,
                ];

                $subtotal += $unitPrice * $quantity;
            }

            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'user_id' => $customer['user_id'] ?? null,
                'customer_name' => $customer['name'],
                'customer_phone' => $customer['phone'],
                'customer_email' => $customer['email'] ?: 'boutique@khalilshop.sn',
                'delivery_region' => 'Retrait en boutique',
                'delivery_city' => 'Retrait en boutique',
                'delivery_address' => 'Vente en magasin — pas de livraison',
                'subtotal' => $subtotal,
                'delivery_fee' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'payment_method' => 'especes',
                'payment_status' => 'paid',
                'is_in_store' => true,
                'status' => 'recue',
            ]);

            // L'encaissement est immédiat et certain pour une vente en boutique — contrairement à
            // payment_status (un simple statut sur la commande), ceci laisse une vraie trace dans
            // `payments`, seule source fiable pour la caisse, la comptabilité et le rapprochement.
            $order->payments()->create([
                'provider' => 'especes',
                'amount' => $subtotal,
                'status' => 'success',
                'paid_at' => now(),
            ]);

            foreach ($lines as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_variant_id' => $line['variant']?->id,
                    'product_name' => $line['product']->name,
                    'variant_label' => $this->variantLabel($line['variant']),
                    'sku' => $line['variant']?->sku,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'subtotal' => $line['subtotal'],
                ]);
            }

            return $order;
        });
    }

    /**
     * Confirme la commande : décrément atomique du stock (docs/SPEC.md §2.3). Appelé automatiquement
     * après un paiement en ligne réussi, ou manuellement par un Gestionnaire/Administrateur pour le
     * paiement à la livraison (§2.4). Si une rupture de stock est détectée à cet instant précis (cas
     * de concurrence rare), la commande est annulée et rien n'est décrémenté.
     */
    public function confirm(Order $order): bool
    {
        try {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    $affected = $item->product_variant_id
                        ? ProductVariant::where('id', $item->product_variant_id)
                            ->where('stock', '>=', $item->quantity)
                            ->decrement('stock', $item->quantity)
                        : Product::where('id', $item->product_id)
                            ->where('stock', '>=', $item->quantity)
                            ->decrement('stock', $item->quantity);

                    if (! $affected) {
                        throw new RuntimeException("Stock insuffisant pour {$item->product_name}.");
                    }
                }

                $order->update(['status' => 'confirmee']);
            });

            return true;
        } catch (RuntimeException $e) {
            $order->update([
                'status' => 'annulee',
                'admin_notes' => 'Rupture de stock détectée à la confirmation : '.$e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Annule la commande. Si le stock avait déjà été décrémenté (commande confirmée ou en préparation),
     * il est automatiquement remis en stock (docs/SPEC.md §2.3).
     */
    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if (in_array($order->status, ['confirmee', 'en_preparation'], true)) {
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::where('id', $item->product_variant_id)->increment('stock', $item->quantity);
                    } else {
                        Product::where('id', $item->product_id)->increment('stock', $item->quantity);
                    }
                }
            }

            $order->update(['status' => 'annulee']);
        });
    }

    /**
     * Email de confirmation au client + notification à l'équipe — appelé une seule fois par
     * commande réellement engagée : immédiatement pour le paiement à la livraison, seulement
     * après confirmation du paiement (IPN PayTech) pour un paiement en ligne, pour ne jamais
     * notifier l'équipe d'une commande jamais payée.
     */
    public function notifyPlaced(Order $order): void
    {
        try {
            Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
        } catch (Throwable $e) {
            report($e);
        }

        Notification::send(User::staff()->get(), new NewOrderNotification($order));
    }

    /**
     * Rattache au compte les commandes passées en tant qu'invité (sans compte), en les
     * retrouvant par email ou numéro de téléphone — dès la connexion, l'inscription, ou
     * l'ajout d'un numéro de téléphone (adresse par défaut, commande) sur un compte existant.
     */
    public function syncGuestOrders(User $user, ?string $phone = null): void
    {
        Order::whereNull('user_id')
            ->where(function ($query) use ($user, $phone) {
                $query->where('customer_email', $user->email);

                if ($phone) {
                    $query->orWhere('customer_phone', $phone);
                }
            })
            ->update(['user_id' => $user->id]);
    }

    private function generateOrderNumber(): string
    {
        return 'KH-'.str_pad((string) (Order::max('id') + 1), 6, '0', STR_PAD_LEFT);
    }

    private function variantLabel(?ProductVariant $variant): ?string
    {
        return $variant?->labelOrNull();
    }
}
