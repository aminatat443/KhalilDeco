<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Delivery;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\PaymentDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cart,
        private readonly OrderService $orders,
        private readonly PaymentDispatcher $payments,
    ) {
    }

    /**
     * Checkout en 4 étapes (section 34 du cahier des charges) : coordonnées, adresse +
     * livraison (combinées), paiement, confirmation — sur une seule page (Alpine).
     */
    public function index(Request $request): View|RedirectResponse
    {
        if (empty($this->cart->items())) {
            // Le panier est vidé dès la création de la commande, avant même la redirection vers
            // le prestataire de paiement en ligne — un client qui revient en arrière depuis
            // PayTech/Wave/Orange Money (au lieu d'utiliser leur propre bouton d'annulation)
            // atterrit donc ici avec un panier vide, sans jamais avoir vu que sa commande existe
            // déjà et reste payable. On le renvoie vers elle plutôt que vers un panier vide qui
            // laisserait croire que la tentative n'a rien laissé.
            $lastOrderId = $request->session()->get('last_order_id');
            $pendingOrder = $lastOrderId ? Order::find($lastOrderId) : null;

            if ($pendingOrder && $pendingOrder->status === 'en_attente_paiement') {
                return redirect()->route('checkout.confirmation', $pendingOrder);
            }

            return redirect()->route('cart.index');
        }

        if ($this->cartHasPendingVariant()) {
            return redirect()->route('cart.index')
                ->withErrors(['cart' => 'Choisissez une taille et une couleur pour chaque article avant de commander.']);
        }

        $defaultAddress = auth()->check()
            ? auth()->user()->addresses()->where('is_default', true)->first()
            : null;

        // Zones à tarif fixe (Malika, Dakar...) d'abord ; celles "à discuter sur WhatsApp"
        // (fee = 0) en dernier, pour ne pas être présélectionnées par défaut.
        $deliveries = Delivery::where('is_active', true)->orderByRaw('(fee = 0) asc, fee asc')->get();

        return view('checkout.index', [
            'deliveries' => $deliveries,
            'defaultAddress' => $defaultAddress,
            'matchedDelivery' => $this->matchDeliveryZone($defaultAddress, $deliveries),
        ]);
    }

    /**
     * Devine la zone de livraison correspondant à une adresse enregistrée (ville/région),
     * pour présélectionner le tarif sans repasser par l'étape de choix. Les zones les plus
     * précises (ex: "Pikine") sont testées avant les plus larges (ex: "Dakar", qui matcherait
     * sinon presque toute adresse de la région du même nom).
     */
    private function matchDeliveryZone($address, \Illuminate\Support\Collection $deliveries): ?Delivery
    {
        if (! $address) {
            return null;
        }

        $haystack = mb_strtolower($address->city.' '.$address->region);

        return $deliveries->first(fn (Delivery $d) => $d->zone !== 'Dakar' && str_contains($haystack, mb_strtolower($d->zone)))
            ?? $deliveries->first(fn (Delivery $d) => str_contains($haystack, mb_strtolower($d->zone)));
    }

    public function store(Request $request): RedirectResponse
    {
        if ($this->cartHasPendingVariant()) {
            return redirect()->route('cart.index')
                ->withErrors(['cart' => 'Choisissez une taille et une couleur pour chaque article avant de commander.']);
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['required', 'email', 'max:255'],
            'delivery_region' => ['required', 'string', 'max:255'],
            'delivery_city' => ['required', 'string', 'max:255'],
            'delivery_quartier' => ['nullable', 'string', 'max:255'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'delivery_instructions' => ['nullable', 'string', 'max:500'],
            'delivery_id' => ['required', 'integer', 'exists:deliveries,id'],
            'payment_method' => ['required', 'in:cod,wave,orange_money,carte,djamo,free_money'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ]);

        $delivery = Delivery::findOrFail($data['delivery_id']);

        $coupon = null;
        if (! empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', $data['coupon_code'])->first();

            if (! $coupon) {
                return back()->withErrors(['coupon_code' => 'Ce code promo est introuvable.'])->withInput();
            }
        }

        try {
            $order = $this->orders->createFromCart(
                customer: [
                    'user_id' => auth()->id(),
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'email' => $data['customer_email'],
                ],
                delivery: [
                    'region' => $data['delivery_region'],
                    'city' => $data['delivery_city'],
                    'quartier' => $data['delivery_quartier'] ?? null,
                    'address' => $data['delivery_address'] ?? '',
                    'instructions' => $data['delivery_instructions'] ?? null,
                ],
                paymentMethod: $data['payment_method'],
                deliveryZone: $delivery,
                coupon: $coupon,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['coupon_code' => $e->getMessage()])->withInput();
        } catch (RuntimeException $e) {
            return back()->withErrors(['cart' => $e->getMessage()]);
        }

        if (auth()->check()) {
            // Mémorise le numéro et l'adresse pour préremplir automatiquement la prochaine
            // commande — le client n'a besoin de les saisir en entier qu'une seule fois.
            auth()->user()->addresses()->updateOrCreate(
                ['is_default' => true],
                [
                    'full_name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'region' => $data['delivery_region'],
                    'city' => $data['delivery_city'],
                    'quartier' => $data['delivery_quartier'] ?? null,
                    'address' => $data['delivery_address'] ?? '',
                    'instructions' => $data['delivery_instructions'] ?? null,
                ]
            );

            // Rattache aussi les éventuelles anciennes commandes invité passées avec ce même
            // numéro de téléphone mais une autre adresse email.
            $this->orders->syncGuestOrders(auth()->user(), $data['customer_phone']);
        }

        $request->session()->put('last_order_id', $order->id);

        // Paiement à la livraison : la commande est déjà engagée, on notifie tout de suite.
        // Paiement en ligne (Wave/Orange Money/Carte) : on ne notifie qu'après confirmation
        // réelle du paiement (voir PayTechController::ipn) — jamais pour une commande jamais payée.
        if ($data['payment_method'] === 'cod') {
            $this->orders->notifyPlaced($order);

            return redirect()->route('checkout.confirmation', $order);
        }

        $payment = $this->payments->initiate($order);

        if (! $payment['success']) {
            return redirect()->route('checkout.confirmation', $order)
                ->withErrors(['payment_method' => 'Le paiement en ligne est momentanément indisponible. '.($payment['message'] ?? '')]);
        }

        return redirect()->away($payment['redirect_url']);
    }

    /**
     * Confirmation (étape 5) — accessible au client propriétaire de la commande, ou juste
     * après création via la session (cas d'une commande invité sans compte).
     */
    public function confirmation(Request $request, Order $order): View
    {
        abort_unless(
            ($order->user_id && $order->user_id === auth()->id())
                || $request->session()->get('last_order_id') === $order->id,
            403
        );

        $order->load('items', 'payments');

        return view('checkout.confirmation', [
            'order' => $order,
            'latestPayment' => $order->payments->sortByDesc('created_at')->first(),
        ]);
    }

    private function cartHasPendingVariant(): bool
    {
        foreach ($this->cart->items() as $item) {
            if (is_null($item['variant']) && $item['product']->variants()->exists()) {
                return true;
            }
        }

        return false;
    }
}
