<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', StockMovement::class);

        $movements = StockMovement::with(['product', 'variant.color', 'variant.size', 'user'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->input('product_id')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('created_at', $request->input('date')))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $products = Product::where('is_active', true)->with('variants')->orderBy('name')->get();

        if ($request->ajax()) {
            return response()->json(['html' => view('admin.stock.partials.list', ['movements' => $movements])->render()]);
        }

        return view('admin.stock.index', [
            'movements' => $movements,
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'exists:product_variants,id'],
            'type' => ['required', 'in:'.StockMovement::TYPE_ENTREE.','.StockMovement::TYPE_SORTIE],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = $data['type'] === StockMovement::TYPE_ENTREE ? 'stock.entry' : 'stock.exit';
        $user = $request->user();
        if ($user->role_id !== null && ! $user->hasPermission($permission)) {
            abort(403, "Vous n'avez pas la permission d'enregistrer ce type de mouvement.");
        }

        DB::transaction(function () use ($data, $user) {
            $delta = $data['type'] === StockMovement::TYPE_ENTREE ? $data['quantity'] : -$data['quantity'];

            if (! empty($data['product_variant_id'])) {
                $variant = ProductVariant::lockForUpdate()->findOrFail($data['product_variant_id']);
                if ($variant->product_id !== (int) $data['product_id']) {
                    throw ValidationException::withMessages(['product_variant_id' => 'Cette variante n\'appartient pas au produit sélectionné.']);
                }
                if ($variant->stock + $delta < 0) {
                    throw ValidationException::withMessages(['quantity' => 'Stock insuffisant pour cette sortie.']);
                }
                $variant->increment('stock', $delta);
                $product = $variant->product;
            } else {
                $product = Product::lockForUpdate()->findOrFail($data['product_id']);
                if ($product->stock + $delta < 0) {
                    throw ValidationException::withMessages(['quantity' => 'Stock insuffisant pour cette sortie.']);
                }
                $product->increment('stock', $delta);
            }

            $movement = StockMovement::create([
                'product_id' => $data['product_id'],
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'user_id' => $user->id,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'reason' => $data['reason'] ?? null,
            ]);

            ActivityLog::record('stock', $data['type'], sprintf(
                '%s a enregistré une %s de %d unité(s) sur %s',
                $user->name,
                $data['type'] === StockMovement::TYPE_ENTREE ? 'entrée' : 'sortie',
                $data['quantity'],
                $product->name,
            ), $movement);
        });

        return redirect()->route('admin.stock.index')->with('status', 'Mouvement enregistré.');
    }
}
