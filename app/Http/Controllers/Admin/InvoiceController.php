<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Registre des factures — une facture par commande (même PDF que celui du client, généré
     * à la volée par InvoiceController@show, section 2.5 de docs/SPEC.md). Cette vue sert de
     * registre comptable : recherche, filtre par période, total de la période affichée.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $query = Order::query()
            ->when($request->filled('q'), fn ($q) => $q->where('order_number', 'ilike', '%'.$request->input('q').'%')
                ->orWhere('customer_name', 'ilike', '%'.$request->input('q').'%'))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')));

        $periodTotal = (clone $query)->sum('total');

        $orders = $query->latest()->paginate(25)->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.invoices.partials.table', ['orders' => $orders, 'periodTotal' => $periodTotal])->render(),
            ]);
        }

        return view('admin.invoices.index', ['orders' => $orders, 'periodTotal' => $periodTotal]);
    }

    /**
     * Écran de création de facture : recherche d'une commande pour la facture définitive,
     * formulaire libre pour la facture pro forma (section demandée séparément de la commande).
     */
    public function create(): View
    {
        $this->authorize('viewAny', Order::class);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'price'])
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'price' => $product->price,
            ]);

        return view('admin.invoices.create', ['products' => $products]);
    }

    /**
     * Recherche en temps réel des commandes (facture définitive) — par numéro, client ou
     * produit commandé, utilisée par l'écran de création de facture.
     */
    public function searchOrders(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $term = trim((string) $request->input('q'));

        if ($term === '') {
            return response()->json(['orders' => []]);
        }

        $orders = Order::query()
            ->where(function ($q) use ($term) {
                $q->where('order_number', 'ilike', '%'.$term.'%')
                    ->orWhere('customer_name', 'ilike', '%'.$term.'%')
                    ->orWhereHas('items', fn ($items) => $items->where('product_name', 'ilike', '%'.$term.'%'));
            })
            ->latest()
            ->limit(15)
            ->get(['id', 'order_number', 'customer_name', 'customer_phone', 'total', 'created_at']);

        return response()->json([
            'orders' => $orders->map(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer_name,
                'phone' => $order->customer_phone,
                'total' => number_format($order->total, 0, ',', ' ').' FCFA',
                'date' => $order->created_at->format('d/m/Y'),
                'url' => $order->invoiceUrl(),
            ]),
        ]);
    }
}
