<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    /**
     * Compte client (section 37 du cahier des charges) : profil, commandes, favoris.
     */
    public function index(): View
    {
        return view('account.index', [
            'user' => auth()->user(),
            'recentOrders' => auth()->user()->orders()->latest()->withCount('items')->with('items.returns')->take(3)->get(),
            'defaultAddress' => auth()->user()->addresses()->where('is_default', true)->first(),
        ]);
    }

    /**
     * Adresse de livraison par défaut — renseignée une fois pour accélérer les prochaines
     * commandes (le checkout la propose automatiquement).
     */
    public function updateAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'region' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'quartier' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'instructions' => ['nullable', 'string', 'max:500'],
        ]);

        auth()->user()->addresses()->updateOrCreate(
            ['is_default' => true],
            $data
        );

        $this->orders->syncGuestOrders(auth()->user(), $data['phone']);

        return back()->with('status', 'Votre adresse de livraison a été enregistrée.');
    }

    /**
     * Historique des commandes.
     */
    public function orders(): View
    {
        return view('account.orders', [
            'orders' => auth()->user()->orders()->latest()->withCount('items')->with('items.returns')->paginate(10),
        ]);
    }

    /**
     * Détail + suivi de commande (section 38 du cahier des charges).
     */
    public function orderShow(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load('items.returns', 'payments');

        return view('account.order-show', [
            'order' => $order,
            'latestPayment' => $order->payments->sortByDesc('created_at')->first(),
        ]);
    }

    /**
     * Suppression définitive du compte, confirmée par une alerte côté client (pas de mot de
     * passe demandé : certains comptes se connectent uniquement via Google et n'en ont pas).
     * Les commandes déjà passées sont conservées (user_id mis à null) ; adresses, avis et
     * favoris sont supprimés en cascade (contraintes de la base de données).
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = auth()->user();

        Auth::logout();
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'Votre compte a été supprimé.');
    }
}
