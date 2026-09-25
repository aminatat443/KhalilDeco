<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    /**
     * Gestion des promotions planifiées (section 46 du cahier des charges).
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Promotion::class);

        $promotions = Promotion::with(['category', 'product'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(function ($q) use ($term) {
                    $q->whereHas('product', fn ($q) => $q->where('name', 'ilike', $term))
                        ->orWhereHas('category', fn ($q) => $q->where('name', 'ilike', $term));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.promotions.partials.table', ['promotions' => $promotions])->render(),
            ]);
        }

        return view('admin.promotions.index', ['promotions' => $promotions]);
    }

    public function create(): View
    {
        $this->authorize('create', Promotion::class);

        return view('admin.promotions.form', [
            'promotion' => new Promotion(),
            'categories' => Category::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Promotion::class);

        Promotion::create($this->validated($request));

        return redirect()->route('admin.promotions.index')->with('status', 'Promotion créée.');
    }

    public function edit(Promotion $promotion): View
    {
        $this->authorize('update', $promotion);

        return view('admin.promotions.form', [
            'promotion' => $promotion,
            'categories' => Category::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $this->authorize('update', $promotion);

        $promotion->update($this->validated($request));

        return redirect()->route('admin.promotions.index')->with('status', 'Promotion mise à jour.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->authorize('delete', $promotion);

        $promotion->delete();

        return redirect()->route('admin.promotions.index')->with('status', 'Promotion supprimée.');
    }

    /**
     * Notifie par email les clients ayant mis en favori un produit concerné par cette promotion
     * — aucune sélection manuelle de produits ou de destinataires, tout est déduit de la
     * promotion elle-même (produit précis ou toute la catégorie) et des favoris existants.
     */
    public function sendEmail(Promotion $promotion): RedirectResponse
    {
        $this->authorize('update', $promotion);

        $productIds = $promotion->product_id
            ? [$promotion->product_id]
            : Product::where('category_id', $promotion->category_id)->pluck('id')->all();

        if (empty($productIds)) {
            return back()->with('status', 'Aucun produit concerné par cette promotion.');
        }

        $userIds = Favorite::whereIn('product_id', $productIds)->distinct()->pluck('user_id');

        if ($userIds->isEmpty()) {
            return back()->with('status', "Aucun client n'a mis en favori un produit concerné — aucun email envoyé.");
        }

        $subjectTemplate = EmailTemplate::findByKey(EmailTemplate::PROMOTION)->subject;

        $campaign = Campaign::create([
            'type' => 'promotion',
            'campaign_type' => Campaign::TYPE_PROMOTION,
            'status' => Campaign::STATUS_PENDING,
            'subject' => Campaign::uniqueSubject($subjectTemplate, Campaign::TYPE_PROMOTION),
            'subject_template' => $subjectTemplate,
            'product_ids' => $productIds,
            'recipients_count' => $userIds->count(),
            'is_automatic' => false,
            'sent_by' => auth()->id(),
        ]);

        $campaign->update(['subject' => Campaign::uniqueSubject($subjectTemplate, Campaign::TYPE_PROMOTION, $campaign->id)]);

        foreach (User::whereIn('id', $userIds)->get(['id', 'email']) as $user) {
            $send = CampaignSend::create([
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'status' => CampaignSend::PENDING,
            ]);

            SendCampaignEmailJob::dispatch($send->id);
        }

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('status', 'Email de promotion mis en file pour '.$userIds->count().' client(s).');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
