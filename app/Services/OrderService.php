<?php

namespace App\Services;

use App\Mail\OrderConfirmationMail;
use App\Models\Coupon;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentAttempt;
use App\Models\PaymentEvent;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Notifications\PaymentCancelledNotification;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentPendingNotification;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
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
            [$nextId, $orderNumber] = $this->reserveOrderNumber();

            $order = Order::create([
                'id' => $nextId,
                'order_number' => $orderNumber,
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

            PaymentEvent::record($order, 'order_created');

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
     * Wave/Orange Money/Carte : aucune commande n'est créée tant que le paiement n'a pas
     * réellement réussi — seule une tentative légère (trace minimale en cas d'échec/abandon) est
     * enregistrée, avec tout ce qu'il faut figé pour matérialiser la commande plus tard depuis
     * l'IPN (voir createFromAttempt), qui arrive côté serveur sans panier de session disponible.
     * Le panier n'est PAS vidé ici — seulement à la création réelle de la commande.
     */
    public function createAttempt(array $customer, array $delivery, string $paymentMethod, Delivery $deliveryZone, ?Coupon $coupon = null): PaymentAttempt
    {
        $items = $this->cart->items();

        if (empty($items)) {
            throw new RuntimeException('Le panier est vide.');
        }

        $subtotal = array_sum(array_column($items, 'subtotal'));
        $discount = $coupon ? $this->promotions->couponDiscount($coupon, $subtotal) : 0;
        $deliveryFee = $deliveryZone->fee;

        $itemsSnapshot = array_map(fn (array $item) => [
            'product_id' => $item['product']->id,
            'product_variant_id' => $item['variant']?->id,
            'product_name' => $item['product']->name,
            'variant_label' => $this->variantLabel($item['variant']),
            'sku' => $item['variant']?->sku,
            'unit_price' => $item['unit_price'],
            'quantity' => $item['quantity'],
            'subtotal' => $item['subtotal'],
        ], $items);

        return PaymentAttempt::create([
            'reference' => 'ATT-'.strtoupper(Str::random(10)),
            'customer_name' => $customer['name'],
            'customer_phone' => $customer['phone'],
            'customer_email' => $customer['email'],
            'user_id' => $customer['user_id'] ?? null,
            'payment_method' => $paymentMethod,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'amount' => max(0, $subtotal + $deliveryFee - $discount),
            'cart_snapshot' => $itemsSnapshot,
            'delivery_snapshot' => [
                'address_id' => $delivery['address_id'] ?? null,
                'delivery_id' => $deliveryZone->id,
                'delivery_zone' => $deliveryZone->zone,
                'delivery_fee' => $deliveryFee,
                'region' => $delivery['region'],
                'city' => $delivery['city'],
                'quartier' => $delivery['quartier'] ?? null,
                'address' => $delivery['address'],
                'instructions' => $delivery['instructions'] ?? null,
            ],
            'coupon_id' => $coupon?->id,
            'status' => 'pending',
        ]);
    }

    /**
     * Matérialise la commande réelle une fois le paiement de la tentative confirmé — jamais
     * avant. Reprend depuis le snapshot figé (jamais depuis le panier live, absent dans le
     * contexte serveur-à-serveur d'un IPN) le rattachement d'adresse par défaut et le
     * regroupement des commandes invité qui, pour createFromCart(), se fait dans
     * CheckoutController::store() juste après la création (auth()->user() n'existe plus ici).
     */
    public function createFromAttempt(PaymentAttempt $attempt): Order
    {
        return DB::transaction(function () use ($attempt) {
            $delivery = $attempt->delivery_snapshot;

            [$nextId, $orderNumber] = $this->reserveOrderNumber();

            $order = Order::create([
                'id' => $nextId,
                'order_number' => $orderNumber,
                'user_id' => $attempt->user_id,
                'address_id' => $delivery['address_id'] ?? null,
                'coupon_id' => $attempt->coupon_id,
                'delivery_id' => $delivery['delivery_id'],
                'customer_name' => $attempt->customer_name,
                'customer_phone' => $attempt->customer_phone,
                'customer_email' => $attempt->customer_email,
                'delivery_region' => $delivery['region'],
                'delivery_zone' => $delivery['delivery_zone'],
                'delivery_city' => $delivery['city'],
                'delivery_quartier' => $delivery['quartier'] ?? null,
                'delivery_address' => $delivery['address'],
                'delivery_instructions' => $delivery['instructions'] ?? null,
                'subtotal' => $attempt->subtotal,
                'delivery_fee' => $delivery['delivery_fee'],
                'discount' => $attempt->discount,
                'total' => $attempt->amount,
                'payment_method' => $attempt->payment_method,
                'payment_status' => 'paid',
                'status' => 'recue',
            ]);

            PaymentEvent::record($order, 'order_created');

            foreach ($attempt->cart_snapshot as $item) {
                $order->items()->create($item);
            }

            if ($attempt->coupon_id) {
                $attempt->coupon?->usages()->create([
                    'order_id' => $order->id,
                    'user_id' => $attempt->user_id,
                ]);
            }

            if ($attempt->user_id && ($user = $attempt->user)) {
                $user->addresses()->updateOrCreate(
                    ['is_default' => true],
                    [
                        'full_name' => $attempt->customer_name,
                        'phone' => $attempt->customer_phone,
                        'region' => $delivery['region'],
                        'city' => $delivery['city'],
                        'quartier' => $delivery['quartier'] ?? null,
                        'address' => $delivery['address'],
                        'instructions' => $delivery['instructions'] ?? null,
                    ]
                );

                $this->syncGuestOrders($user, $attempt->customer_phone);
            }

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

            [$nextId, $orderNumber] = $this->reserveOrderNumber();

            $order = Order::create([
                'id' => $nextId,
                'order_number' => $orderNumber,
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

                $oldStatus = $order->status;
                $order->update(['status' => 'confirmee']);
                PaymentEvent::record($order, 'order_confirmed', ['old_status' => $oldStatus]);
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
     * Email de confirmation au client + notification à l'équipe — paiement à la livraison
     * uniquement : la commande est déjà actionnable dès sa création, pas d'étape d'attente.
     * Pour un paiement en ligne, voir notifyPendingPayment() (à la création) puis
     * notifyPaymentReceived() (après confirmation réelle du paiement) — jamais celle-ci.
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
     * Commande en ligne tout juste créée, paiement pas encore tenté/confirmé — l'équipe doit
     * savoir qu'une commande attend un paiement sans pour autant la traiter comme actionnable.
     * Pas d'email client ici (il recevra la confirmation réelle après paiement, voir
     * notifyPaymentReceived()) pour éviter deux emails à quelques minutes d'écart.
     */
    public function notifyPendingPayment(Order $order): void
    {
        Notification::send($this->staffWithPermission('orders.view'), new PaymentPendingNotification($order));
    }

    /**
     * Paiement confirmé côté serveur (IPN vérifié) : email de confirmation au client (jamais
     * envoyé avant, voir createFromCart) + notification à l'équipe comptable/caisse. Utilisée par
     * Djamo/Free Money (PayDunya), dont la commande existait déjà avant paiement — comportement
     * inchangé, voir notifyNewOnlineOrder() pour Wave/Orange Money/Carte où ce n'est pas le cas.
     */
    public function notifyPaymentReceived(Order $order, Payment $payment): void
    {
        try {
            Mail::to($order->customer_email)->send(new OrderConfirmationMail($order));
        } catch (Throwable $e) {
            report($e);
        }

        Notification::send($this->staffWithPermission('payments.view'), new PaymentReceivedNotification($order, $payment));
    }

    /**
     * Wave/Orange Money/Carte uniquement (voir createFromAttempt) : la commande n'existait pas
     * avant l'instant précis où le paiement est confirmé — "nouvelle commande" et "paiement reçu"
     * sont donc deux faits annoncés en même temps, contrairement à Djamo/Free Money où ils restent
     * séparés dans le temps (notifyPendingPayment() puis notifyPaymentReceived() plus tard).
     */
    public function notifyNewOnlineOrder(Order $order, Payment $payment): void
    {
        Notification::send($this->staffWithPermission('orders.view'), new NewOrderNotification($order));

        $this->notifyPaymentReceived($order, $payment);
    }

    public function notifyPaymentFailed(Order $order): void
    {
        Notification::send($this->staffWithPermission('payments.view'), new PaymentFailedNotification($order));
    }

    public function notifyPaymentCancelled(Order $order): void
    {
        Notification::send($this->staffWithPermission('payments.view'), new PaymentCancelledNotification($order));
    }

    /**
     * Ciblage par permission plutôt que "tout le staff" (section 6/28 du cahier des charges) —
     * le Super Admin reçoit toujours tout, hasPermission() le court-circuite déjà à true.
     *
     * @return Collection<int, User>
     */
    private function staffWithPermission(string $permission): Collection
    {
        return User::staff()->get()->filter(fn (User $user) => $user->hasPermission($permission))->values();
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

    /**
     * `Order::max('id') + 1` se désynchronisait dès qu'une commande était supprimée (nettoyage
     * de données de test, notamment) : le prochain id réellement attribué par la séquence sautait
     * la valeur manquante alors que ce calcul, lui, continuait de la prédire — deux commandes
     * pouvaient alors recevoir le même order_number, et donc la même référence PayTech
     * (ref_command), que PayTech rejette comme déjà utilisée. `nextval()` sur la séquence réelle
     * de la table ne réutilise jamais une valeur, quelles que soient les suppressions.
     *
     * @return array{0: int, 1: string}
     */
    private function reserveOrderNumber(): array
    {
        $nextId = (int) DB::selectOne("SELECT nextval(pg_get_serial_sequence('orders', 'id')) AS id")->id;

        return [$nextId, 'KH-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT)];
    }

    private function variantLabel(?ProductVariant $variant): ?string
    {
        return $variant?->labelOrNull();
    }
}
