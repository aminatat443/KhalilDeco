<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Zones et tarifs de livraison — section unique de vérité pour tout le site (panier, checkout,
 * récapitulatif, calcul du total) : Configuration → Zones et tarifs de livraison. Une fois un
 * tarif modifié ici, il s'applique immédiatement à toute nouvelle commande, sans rien à changer
 * ailleurs dans le code (voir CheckoutController et OrderService, qui lisent ce modèle en direct).
 */
class DeliveryController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Delivery::class);

        return view('admin.deliveries.index', [
            'deliveries' => Delivery::withCount('orders')->orderBy('fee')->get(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Delivery::class);

        return view('admin.deliveries.form', ['delivery' => new Delivery()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Delivery::class);

        Delivery::create($this->validated($request));

        return redirect()->route('admin.deliveries.index')->with('status', 'Zone de livraison créée.');
    }

    public function edit(Delivery $delivery): View
    {
        $this->authorize('update', $delivery);

        return view('admin.deliveries.form', ['delivery' => $delivery]);
    }

    public function update(Request $request, Delivery $delivery): RedirectResponse
    {
        $this->authorize('update', $delivery);

        $delivery->update($this->validated($request));

        return redirect()->route('admin.deliveries.index')->with('status', 'Zone « '.$delivery->zone.' » mise à jour — le nouveau tarif s\'applique immédiatement aux nouvelles commandes.');
    }

    /**
     * Suppression définitive réservée aux zones jamais utilisées (section 9 du cahier des
     * charges) — sinon on perdrait le nom de zone affiché sur d'anciennes commandes malgré le
     * snapshot Order::delivery_zone. Désactiver reste toujours possible via update().
     */
    public function destroy(Delivery $delivery): RedirectResponse
    {
        $this->authorize('delete', $delivery);

        if ($delivery->orders()->exists()) {
            return back()->with('status', 'Cette zone a déjà été utilisée dans des commandes — désactivez-la plutôt que de la supprimer, pour conserver l\'historique.');
        }

        $delivery->delete();

        return redirect()->route('admin.deliveries.index')->with('status', 'Zone de livraison supprimée.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'zone' => ['required', 'string', 'max:255'],
            'fee' => ['required', 'integer', 'min:0'],
            'estimated_days' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
