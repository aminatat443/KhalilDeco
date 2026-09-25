<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\CustomerSegmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerSegmentController extends Controller
{
    public function index(CustomerSegmentService $service): View
    {
        $this->authorize('viewAny', Campaign::class);

        return view('admin.segments.index', [
            'segments' => CustomerSegmentService::SEGMENTS,
            'counts' => $service->counts(),
            'categories' => $service->categories(),
        ]);
    }

    /**
     * Compte en direct les destinataires d'un segment, pour éviter d'envoyer "à l'aveugle" une
     * campagne personnalisée depuis le formulaire de création.
     */
    public function count(Request $request, CustomerSegmentService $service): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'segment' => ['required', 'in:'.implode(',', array_keys(CustomerSegmentService::SEGMENTS))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $count = $service->resolve($data['segment'], $data['category_id'] ?? null)->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Détail d'un segment (section 2) : liste réelle des abonnés éligibles, avec recherche.
     */
    public function show(Request $request, string $segment, CustomerSegmentService $service): View|JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        abort_unless(array_key_exists($segment, CustomerSegmentService::SEGMENTS), 404);

        $categoryId = $request->integer('category_id') ?: null;

        if ($segment === 'category_buyers' && ! $categoryId) {
            return view('admin.segments.show', [
                'segment' => $segment,
                'label' => CustomerSegmentService::SEGMENTS[$segment],
                'description' => CustomerSegmentService::DESCRIPTIONS[$segment],
                'categories' => $service->categories(),
                'categoryId' => null,
                'clients' => collect(),
                'needsCategory' => true,
            ]);
        }

        $clients = $service->subscriberDetails($segment, $categoryId);

        if ($request->filled('q')) {
            $term = mb_strtolower($request->input('q'));
            $clients = $clients->filter(fn ($c) => str_contains(mb_strtolower($c['name']), $term) || str_contains(mb_strtolower($c['email']), $term))->values();
        }

        if ($request->ajax()) {
            return response()->json(['html' => view('admin.segments.partials.clients', ['clients' => $clients])->render()]);
        }

        return view('admin.segments.show', [
            'segment' => $segment,
            'label' => CustomerSegmentService::SEGMENTS[$segment],
            'description' => CustomerSegmentService::DESCRIPTIONS[$segment],
            'categories' => $service->categories(),
            'categoryId' => $categoryId,
            'clients' => $clients,
            'needsCategory' => false,
        ]);
    }
}
