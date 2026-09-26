<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use App\Services\OrderService;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    /**
     * Gestion des commandes (section 44 du cahier des charges).
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::query()
            ->when($request->filled('q'), fn ($q) => $q->where('order_number', 'ilike', '%'.$request->input('q').'%')
                ->orWhere('customer_name', 'ilike', '%'.$request->input('q').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->input('date')))
            ->when($request->filled('month'), function ($q) use ($request) {
                $month = Carbon::createFromFormat('Y-m', $request->input('month'));
                $q->whereYear('created_at', $month->year)->whereMonth('created_at', $month->month);
            })
            ->when($request->filled('year'), fn ($q) => $q->whereYear('created_at', $request->input('year')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
            ->when($request->filled('payment_method'), fn ($q) => $q->where('payment_method', $request->input('payment_method')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->input('payment_status')))
            ->when($request->input('flag') === 'overdue_shipping', fn ($q) => $q->overdueShipping())
            ->when($request->input('flag') === 'confirmed_sales', fn ($q) => $q->whereIn('status', Order::CONFIRMED_STATUSES))
            ->when($request->input('flag') === 'unpaid', fn ($q) => $q->where('payment_status', '!=', 'paid')->where('status', '!=', 'annulee'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.orders.partials.table', ['orders' => $orders])->render(),
            ]);
        }

        $statusCounts = Order::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.orders.index', [
            'orders' => $orders,
            'todayCount' => Order::whereDate('created_at', today())->count(),
            'statusCounts' => $statusCounts,
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order->load('items', 'user', 'coupon', 'payments', 'paymentEvents');

        return view('admin.orders.show', ['order' => $order]);
    }

    /**
     * Contenu de la modale de détail unique (OrderDetailsModal) — ouverte depuis une notification
     * ou pour un rafraîchissement en direct pendant qu'elle reste affichée (section 8/12 du cahier
     * des charges). La permission est vérifiée avant tout rendu ; un refus renvoie un message
     * clair en JSON plutôt que la page 403 générique.
     */
    public function modal(Order $order): JsonResponse
    {
        try {
            $this->authorize('view', $order);
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            return response()->json([
                'message' => 'Accès refusé : vous n\'avez pas les permissions nécessaires pour consulter cette commande.',
            ], 403);
        }

        $order->load('items', 'user', 'coupon', 'payments', 'paymentEvents');

        return response()->json([
            'html' => view('admin.orders.partials.modal-content', ['order' => $order])->render(),
        ]);
    }

    /**
     * Formulaire de vente conclue directement en boutique (section "commande sur place").
     */
    public function create(): View
    {
        $this->authorize('create', Order::class);

        $products = Product::query()
            ->where('is_active', true)
            ->with(['images' => fn ($q) => $q->orderBy('sort_order'), 'variants.attributeValues.attribute'])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'stock' => $product->stock,
                'image' => $product->images->first()?->url,
                'variants' => $product->variants->map(fn ($variant) => [
                    'id' => $variant->id,
                    'label' => $variant->label(),
                    'price' => $variant->price,
                    'stock' => $variant->stock,
                ]),
            ]);

        return view('admin.orders.create', ['products' => $products]);
    }

    /**
     * Recherche en temps réel d'un client déjà inscrit, pour préremplir le formulaire de vente
     * en boutique sans ressaisir ses coordonnées (section "commande sur place").
     */
    public function searchClients(Request $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $query = trim((string) $request->input('q'));

        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $clients = User::query()
            ->where('role', Role::Client)
            // Exclut les comptes équipe RBAC (Caissier, Comptable...) — ils partagent l'ancien
            // enum "client" faute d'équivalent hiérarchique, mais ne sont pas des clients à
            // rattacher à une commande.
            ->whereNull('role_id')
            ->where(fn ($q) => $q->where('name', 'ilike', "%{$query}%")->orWhere('email', 'ilike', "%{$query}%"))
            ->with(['addresses' => fn ($q) => $q->where('is_default', true)])
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(function (User $user) {
                $address = $user->addresses->first();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $address?->phone ?? '',
                    'address' => $address
                        ? collect([$address->address, $address->quartier, $address->city, $address->region])->filter()->join(', ')
                        : '',
                ];
            });

        return response()->json($clients);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Order::class);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variant_id' => [
                'nullable', 'integer', 'exists:product_variants,id',
                function ($attribute, $value, $fail) use ($request) {
                    $index = explode('.', $attribute)[1];
                    $productId = $request->input("items.{$index}.product_id");

                    if (! $value && $productId && Product::find($productId)?->variants()->exists()) {
                        $fail('Choisissez une taille/couleur pour ce produit.');
                    }

                    if ($value && $productId) {
                        $variant = \App\Models\ProductVariant::find($value);
                        if ($variant && $variant->product_id !== (int) $productId) {
                            $fail('La variante sélectionnée ne correspond pas au produit.');
                        }
                    }
                },
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $order = $this->orders->createInStore(
                ['name' => $data['customer_name'], 'phone' => $data['customer_phone'], 'email' => $data['customer_email'] ?? null, 'user_id' => $data['user_id'] ?? null],
                $data['items'],
            );
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['items' => 'Impossible d\'enregistrer la vente : '.$e->getMessage()]);
        }

        $success = $this->orders->confirm($order);

        return redirect()->route('admin.orders.show', $order)->with('status', $success
            ? 'Vente enregistrée et stock mis à jour.'
            : 'Vente enregistrée, mais rupture de stock détectée — vérifiez la commande.');
    }

    /**
     * Confirmation manuelle (paiement à la livraison) : décrément atomique du stock
     * (docs/SPEC.md §2.3/§2.4).
     */
    public function confirm(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $order);

        // Garde-fou serveur : une commande réglée en ligne (Wave/Orange Money/Carte) ne doit
        // JAMAIS pouvoir être confirmée manuellement tant que son paiement n'est pas
        // effectivement passé à "paid" par la vérification serveur (webhook/IPN) — même via un
        // appel direct à cette route. Seul le paiement à la livraison se confirme manuellement.
        if ($order->payment_method !== 'cod' && $order->payment_status !== 'paid') {
            $message = 'Impossible de confirmer : le paiement en ligne de cette commande n\'a pas encore été validé.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['status' => $message]);
        }

        $success = $this->orders->confirm($order);

        $this->notifyStatus($order);

        $message = $success
            ? 'Commande confirmée, stock mis à jour.'
            : 'Rupture de stock détectée — la commande a été annulée automatiquement.';

        return $this->statusResponse($request, $order, $message);
    }

    public function cancel(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $this->authorize('cancel', $order);

        $this->orders->cancel($order);

        $this->notifyStatus($order);

        return $this->statusResponse($request, $order, 'Commande annulée, stock restauré si nécessaire.');
    }

    /**
     * Changement de statut logistique (en préparation / expédiée / livrée) — sans impact sur le stock.
     */
    public function updateStatus(Request $request, Order $order): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $order);

        $data = $request->validate([
            'status' => ['required', 'in:en_preparation,expediee,livree'],
        ]);

        $order->update(['status' => $data['status']]);

        $this->notifyStatus($order);

        return $this->statusResponse($request, $order, 'Statut mis à jour.');
    }

    /**
     * Après un changement de statut (confirmer/annuler/étape logistique) : la fiche commande
     * (badge, suivi, actions) se ré-affiche en place côté client sans recharger la page — même
     * vue Blade rendue côté serveur, juste injectée via innerHTML plutôt que via une navigation.
     */
    private function statusResponse(Request $request, Order $order, string $message): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            $order->load('items', 'user', 'coupon', 'payments', 'paymentEvents');

            return response()->json([
                'message' => $message,
                'html' => view('admin.orders.partials.detail', ['order' => $order])->render(),
            ]);
        }

        return back()->with('status', $message);
    }

    /**
     * Email de suivi envoyé au client à chaque changement de statut — l'échec d'envoi (Brevo
     * indisponible, etc.) ne doit jamais faire échouer l'action admin déjà appliquée en base.
     */
    private function notifyStatus(Order $order): void
    {
        $order = $order->fresh();

        try {
            Mail::to($order->customer_email)->send(new OrderStatusMail($order));
        } catch (Throwable $e) {
            report($e);
        }

        // Notification en app — seulement si la commande est rattachée à un compte (une
        // commande invité n'a personne à notifier côté client).
        $order->user?->notify(new OrderStatusNotification($order));
    }
}
