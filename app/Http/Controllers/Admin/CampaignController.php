<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendCampaignEmailJob;
use App\Mail\NewArrivalsMail;
use App\Mail\NewsletterMail;
use App\Mail\PromotionMail;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\User;
use App\Services\CampaignCatalogResolver;
use App\Services\CampaignDispatcher;
use App\Services\CustomerSegmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function __construct(
        private readonly CampaignCatalogResolver $catalog,
        private readonly CampaignDispatcher $dispatcher,
        private readonly CustomerSegmentService $segments,
    ) {
    }

    /**
     * Registre unifié de tous les types de campagne — section 10 du cahier des charges.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Campaign::class);

        $subscribersCount = NewsletterSubscriber::whereNull('unsubscribed_at')->count();

        return view('admin.campaigns.index', [
            'campaigns' => Campaign::with('sender')
                // Bornes transmises par la carte "Emails envoyés" du tableau de bord — reproduit
                // exactement la période sur laquelle $emailsSent/$campaignsFailed ont été calculés.
                ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->input('from')))
                ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->input('to')))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'stats' => [
                'total' => Campaign::count(),
                'sent_this_month' => CampaignSend::where('status', CampaignSend::SENT)->whereMonth('sent_at', now()->month)->whereYear('sent_at', now()->year)->count(),
                'sent_today' => CampaignSend::where('status', CampaignSend::SENT)->whereDate('sent_at', now())->count(),
                'newsletter_subscribers' => $subscribersCount,
            ],
            'automaticCampaigns' => [
                [
                    'type' => Campaign::TYPE_NEW_ARRIVALS,
                    'label' => 'Nouveautés',
                    'products_count' => $this->catalog->newArrivalsProducts()->count(),
                    'subscribers_count' => $subscribersCount,
                    'last' => Campaign::where('campaign_type', Campaign::TYPE_NEW_ARRIVALS)->latest()->first(),
                    'send_route' => route('admin.campaigns.send-new-arrivals'),
                ],
                [
                    'type' => Campaign::TYPE_ACTIVE_PROMOTIONS,
                    'label' => 'Promotions',
                    'products_count' => $this->catalog->activePromotionProducts()->count(),
                    'subscribers_count' => $subscribersCount,
                    'last' => Campaign::where('campaign_type', Campaign::TYPE_ACTIVE_PROMOTIONS)->latest()->first(),
                    'send_route' => route('admin.campaigns.send-promotions'),
                ],
            ],
        ]);
    }

    /**
     * "Nouvelle campagne" — 3 types au choix (section 1 du cahier des charges) : Nouveautés et
     * Promotions détectent automatiquement les produits concernés depuis le catalogue (sections 2
     * et 3, l'administrateur ne sélectionne jamais de produit à la main) ; la Personnalisée reste
     * un contenu libre avec sélection de produits facultative (section 4).
     */
    public function create(): View
    {
        $this->authorize('create', Campaign::class);

        return view('admin.campaigns.form', [
            'subscribersCount' => NewsletterSubscriber::whereNull('unsubscribed_at')->count(),
            'newArrivalsProducts' => $this->catalog->newArrivalsProducts()->map(fn (Product $p) => $this->newArrivalDisplay($p))->values(),
            'activePromotionsProducts' => $this->catalog->activePromotionProducts()->map(fn (Product $p) => $this->promotionDisplay($p))->values(),
            'newArrivalsTemplate' => EmailTemplate::findByKey(EmailTemplate::NEW_ARRIVALS),
            'activePromotionsTemplate' => EmailTemplate::findByKey(EmailTemplate::ACTIVE_PROMOTIONS),
            'categoryTree' => Category::whereNull('parent_id')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')])
                ->get(['id', 'name', 'parent_id']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Campagne "Personnalisée" — objet/titre/contenu/image/bouton libres, produits facultatifs
     * (section 4). Peut être envoyée immédiatement ou programmée (section 9).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'image_url' => ['nullable', 'url', 'max:2000'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'url', 'max:2000'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'segment' => ['nullable', 'in:'.implode(',', array_keys(CustomerSegmentService::SEGMENTS))],
            'segment_category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ] + $this->scheduleRules());

        $scheduledAt = $this->resolveScheduledAt($data);

        if ($scheduledAt === false) {
            return back()->withErrors(['scheduled_time' => "La date et l'heure programmées doivent être dans le futur."])->withInput();
        }

        return $this->createCampaign(
            Campaign::TYPE_NEWSLETTER,
            $data['subject'],
            $data['product_ids'] ?? null,
            [
                'title' => $data['title'] ?? null,
                'message' => $data['message'],
                'image_url' => $data['image_url'] ?? null,
                'button_text' => $data['button_text'] ?? null,
                'button_url' => $data['button_url'] ?? null,
            ],
            $scheduledAt,
            $data['segment'] ?? 'all',
            $data['segment_category_id'] ?? null,
        );
    }

    /**
     * "Nouveautés" — envoie automatiquement tous les produits marqués [is_new] aux abonnés
     * newsletter, sans sélection manuelle (section 2).
     */
    public function sendNewArrivals(Request $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ] + $this->scheduleRules());

        $productIds = $this->catalog->newArrivalsProducts()->pluck('id')->all();

        if (empty($productIds)) {
            return back()->with('status', 'Aucun produit marqué comme nouveauté dans le catalogue.');
        }

        $scheduledAt = $this->resolveScheduledAt($data);

        if ($scheduledAt === false) {
            return back()->withErrors(['scheduled_time' => "La date et l'heure programmées doivent être dans le futur."])->withInput();
        }

        $template = EmailTemplate::findByKey(EmailTemplate::NEW_ARRIVALS);

        return $this->createCampaign(
            Campaign::TYPE_NEW_ARRIVALS,
            ($data['subject'] ?? null) ?: $template->subject,
            $productIds,
            ['title' => $data['title'] ?? null, 'message' => $data['message'] ?? null],
            $scheduledAt
        );
    }

    /**
     * "Promotions" — envoie automatiquement tous les produits actuellement en promotion aux
     * abonnés newsletter, sans sélection manuelle (section 3).
     */
    public function sendActivePromotions(Request $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
        ] + $this->scheduleRules());

        $productIds = $this->catalog->activePromotionProducts()->pluck('id')->all();

        if (empty($productIds)) {
            return back()->with('status', 'Aucun produit actuellement en promotion dans le catalogue.');
        }

        $scheduledAt = $this->resolveScheduledAt($data);

        if ($scheduledAt === false) {
            return back()->withErrors(['scheduled_time' => "La date et l'heure programmées doivent être dans le futur."])->withInput();
        }

        $template = EmailTemplate::findByKey(EmailTemplate::ACTIVE_PROMOTIONS);

        return $this->createCampaign(
            Campaign::TYPE_ACTIVE_PROMOTIONS,
            ($data['subject'] ?? null) ?: $template->subject,
            $productIds,
            ['title' => $data['title'] ?? null, 'message' => $data['message'] ?? null],
            $scheduledAt
        );
    }

    /**
     * Rendu réel de l'email en cours de rédaction, sans rien enregistrer — section 6 du cahier
     * des charges. Ouvert dans un nouvel onglet.
     */
    public function previewDraft(Request $request): Response
    {
        $this->authorize('viewAny', Campaign::class);

        $data = $request->validate($this->draftRules());
        $mailable = $this->buildMailable($data, auth()->user());
        $subject = $mailable->envelope()->subject;

        $banner = '<div style="background:#263F3A;color:#fff;padding:12px 24px;font:13px/1.5 Helvetica,Arial,sans-serif;">'
            .'<strong style="text-transform:uppercase;letter-spacing:0.05em;font-size:11px;">Objet</strong><br>'
            .e($subject)
            .'</div>';

        return response($banner.$mailable->render());
    }

    /**
     * "Envoyer un email test" — section 8 : l'administrateur reçoit exactement le rendu qui sera
     * envoyé aux abonnés, sans créer de campagne ni toucher à l'historique.
     */
    public function sendTest(Request $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ] + $this->draftRules());

        try {
            Mail::to($data['test_email'])->send($this->buildMailable($data, auth()->user()));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => "Échec de l'envoi test : ".$e->getMessage()], 422);
        }

        return response()->json(['message' => 'Email test envoyé à '.$data['test_email'].'.']);
    }

    /**
     * Recherche produit pour le sélecteur de la campagne Personnalisée — nom, référence (modèle)
     * ou catégorie (section 4).
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $q = trim((string) $request->input('q'));
        $subcategoryId = $request->input('subcategory_id');
        $categoryId = $request->input('category_id');

        $products = Product::query()
            ->where('is_active', true)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'ilike', "%{$q}%")
                        ->orWhere('model', 'ilike', "%{$q}%")
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'ilike', "%{$q}%"));
                });
            })
            ->when($subcategoryId, fn ($query) => $query->where('category_id', $subcategoryId))
            ->when($categoryId && ! $subcategoryId, function ($query) use ($categoryId) {
                $ids = Category::where('id', $categoryId)->orWhere('parent_id', $categoryId)->pluck('id');
                $query->whereIn('category_id', $ids);
            })
            ->with(['images', 'category'])
            ->orderBy('name')
            ->take(20)
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'model' => $p->model,
                'category' => $p->category?->name,
                'price' => $p->price,
                'image' => $p->images->first()?->url,
            ]);

        return response()->json(['products' => $products]);
    }

    public function show(Campaign $campaign): View|JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $campaign->load('sender');
        $sends = $campaign->sends()->with('user')->latest()->paginate(30);

        if (request()->ajax()) {
            return response()->json(['html' => view('admin.campaigns.partials.sends-detail-table', [
                'campaign' => $campaign,
                'sends' => $sends,
            ])->render()]);
        }

        return view('admin.campaigns.show', [
            'campaign' => $campaign,
            'sends' => $sends,
        ]);
    }

    /**
     * "Relancer" une campagne restée "en attente" (section 15) : remet en file les envois
     * jamais traités. Un envoi reste bloqué ainsi lorsqu'aucun worker de file (`php artisan
     * queue:work`) n'a tourné entre sa mise en file et maintenant — SendCampaignEmailJob lui-même
     * est idempotent (il ignore un CampaignSend qui n'est plus PENDING), donc relancer une
     * campagne déjà entièrement traitée ne duplique rien, ça ne fait juste rien.
     */
    public function retry(Campaign $campaign): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $pendingIds = $campaign->sends()->where('status', CampaignSend::PENDING)->pluck('id');

        if ($pendingIds->isEmpty()) {
            return back()->with('status', 'Aucun envoi en attente pour cette campagne — rien à relancer.');
        }

        foreach ($pendingIds as $sendId) {
            SendCampaignEmailJob::dispatch($sendId);
        }

        return back()->with('status', $pendingIds->count().' envoi(s) remis en file.');
    }

    /**
     * Liste des abonnés newsletter — destinataires des campagnes Nouveautés/Promotions/
     * Personnalisée (section 5 du cahier des charges).
     */
    public function subscribers(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $subscribers = NewsletterSubscriber::query()
            ->with('user')
            ->when($request->filled('q'), fn ($q) => $q->where('email', 'ilike', '%'.$request->input('q').'%'))
            ->when($request->input('status') === 'active', fn ($q) => $q->whereNull('unsubscribed_at'))
            ->when($request->input('status') === 'unsubscribed', fn ($q) => $q->whereNotNull('unsubscribed_at'))
            ->latest('subscribed_at')
            ->paginate(30)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.campaigns.partials.subscribers-table', ['subscribers' => $subscribers])->render(),
            ]);
        }

        return view('admin.campaigns.subscribers', ['subscribers' => $subscribers]);
    }

    private function newArrivalDisplay(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'image' => $product->images->first()?->url,
            'price' => $product->price,
            'description' => $product->description ? Str::limit($product->description, 90) : null,
            'category' => $product->category?->name,
            'url' => route('products.show', $product),
        ];
    }

    private function promotionDisplay(Product $product): array
    {
        $discountPercent = $product->old_price > $product->price
            ? (int) round((($product->old_price - $product->price) / $product->old_price) * 100)
            : 0;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'image' => $product->images->first()?->url,
            'old_price' => $product->old_price,
            'new_price' => $product->price,
            'discount_label' => '-'.$discountPercent.'%',
            'url' => route('products.show', $product),
        ];
    }

    /**
     * Règles communes à l'aperçu et à l'envoi test — champs de brouillon, jamais persistés.
     */
    private function draftRules(): array
    {
        return [
            'campaign_type' => ['required', 'in:new_arrivals,active_promotions,newsletter'],
            'subject' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'image_url' => ['nullable', 'string', 'max:2000'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:2000'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
        ];
    }

    private function scheduleRules(): array
    {
        return [
            // Facultatif : le bouton "Envoyer" rapide (aperçu des campagnes automatiques) n'a pas
            // de champ de planification et doit envoyer immédiatement — seule la modale "Nouvelle
            // campagne" expose le choix "Programmer l'envoi".
            'send_mode' => ['nullable', 'in:now,schedule'],
            'scheduled_date' => ['required_if:send_mode,schedule', 'nullable', 'date'],
            'scheduled_time' => ['required_if:send_mode,schedule', 'nullable', 'date_format:H:i'],
        ];
    }

    /**
     * @return Carbon|null|false  null = envoi immédiat, false = date/heure invalide (passée), Carbon = programmée
     */
    private function resolveScheduledAt(array $data): Carbon|null|false
    {
        if (($data['send_mode'] ?? 'now') !== 'schedule') {
            return null;
        }

        $scheduledAt = Carbon::parse($data['scheduled_date'].' '.$data['scheduled_time']);

        return $scheduledAt->isFuture() ? $scheduledAt : false;
    }

    /**
     * Construit le mailable exact qui sera envoyé, à partir de champs de brouillon non enregistrés
     * — utilisé à la fois par l'aperçu et l'envoi test (section 6 et 8) pour garantir qu'ils
     * montrent/envoient réellement ce qui sera envoyé aux abonnés.
     */
    private function buildMailable(array $data, User $user): Mailable
    {
        // Même modèle que pour un envoi réel (section "identifiant automatique") : l'aperçu et
        // le test doivent montrer l'objet exact qu'une campagne créée à cet instant recevrait,
        // suffixe de date compris — jamais l'objet brut du modèle.
        $templateSubject = match ($data['campaign_type']) {
            Campaign::TYPE_NEW_ARRIVALS => ($data['subject'] ?? null) ?: EmailTemplate::findByKey(EmailTemplate::NEW_ARRIVALS)->subject,
            Campaign::TYPE_ACTIVE_PROMOTIONS => ($data['subject'] ?? null) ?: EmailTemplate::findByKey(EmailTemplate::ACTIVE_PROMOTIONS)->subject,
            default => $data['subject'] ?? '',
        };

        $finalSubject = $templateSubject !== '' ? Campaign::uniqueSubject($templateSubject, $data['campaign_type']) : null;

        $draft = new Campaign([
            'subject' => $finalSubject,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'button_text' => $data['button_text'] ?? null,
            'button_url' => $data['button_url'] ?? null,
            'product_ids' => $data['product_ids'] ?? null,
        ]);

        return match ($data['campaign_type']) {
            Campaign::TYPE_NEW_ARRIVALS => new NewArrivalsMail(
                $user,
                $this->catalog->newArrivalsProducts()->map(fn (Product $p) => $this->newArrivalDisplay($p))->values(),
                $draft,
            ),
            Campaign::TYPE_ACTIVE_PROMOTIONS => new PromotionMail(
                $user,
                $this->catalog->activePromotionProducts()->map(fn (Product $p) => $this->promotionDisplay($p))->values(),
                EmailTemplate::ACTIVE_PROMOTIONS,
                $draft,
            ),
            default => (function () use ($draft, $user) {
                $send = new CampaignSend(['email' => $user->email]);
                $send->setRelation('campaign', $draft);
                $send->setRelation('user', $user);

                return new NewsletterMail($send);
            })(),
        };
    }

    private function createCampaign(string $campaignType, string $subject, ?array $productIds, array $extra, ?Carbon $scheduledAt, string $segment = 'all', ?int $segmentCategoryId = null): RedirectResponse
    {
        $subscribers = $this->segments->resolve($segment, $segmentCategoryId);

        if ($subscribers->isEmpty()) {
            return back()->with('status', 'Aucun destinataire dans ce segment — aucun email envoyé.');
        }

        $campaign = Campaign::create([
            ...$extra,
            'type' => 'annonce',
            'campaign_type' => $campaignType,
            'status' => $scheduledAt ? Campaign::STATUS_SCHEDULED : Campaign::STATUS_PENDING,
            'subject' => $subject,
            'subject_template' => $subject,
            'product_ids' => $productIds,
            'recipients_count' => $subscribers->count(),
            'is_automatic' => false,
            'scheduled_at' => $scheduledAt,
            'sent_by' => auth()->id(),
        ]);

        if ($scheduledAt) {
            return redirect()->route('admin.campaigns.show', $campaign)
                ->with('status', 'Campagne programmée pour le '.$scheduledAt->translatedFormat('d/m/Y à H:i').'.');
        }

        $this->dispatcher->activate($campaign, $subscribers);

        return redirect()->route('admin.campaigns.show', $campaign)
            ->with('status', 'Campagne mise en file pour '.$subscribers->count().' abonné(s).');
    }
}
