<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AbandonedCartMail;
use App\Mail\LowStockFavoriteMail;
use App\Mail\NewArrivalsMail;
use App\Mail\PromotionMail;
use App\Models\Campaign;
use App\Models\Cart;
use App\Models\EmailTemplate;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EmailTemplateController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Campaign::class);

        $order = array_flip(array_keys(EmailTemplate::KEYS));

        return view('admin.email-templates.index', [
            'templates' => EmailTemplate::all()->sortBy(fn (EmailTemplate $t) => $order[$t->key] ?? 99)->values(),
        ]);
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        $this->authorize('viewAny', Campaign::class);

        return view('admin.email-templates.edit', ['template' => $emailTemplate]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:5000'],
            'button_text' => ['nullable', 'string', 'max:100'],
        ]);

        $emailTemplate->update($data);

        return back()->with('status', 'Modèle « '.EmailTemplate::KEYS[$emailTemplate->key].' » mis à jour.');
    }

    /**
     * Rendu réel du modèle avec des données d'exemple — section 14 du cahier des charges.
     * Ouvert dans un nouvel onglet, jamais envoyé.
     */
    public function preview(EmailTemplate $emailTemplate): Response
    {
        $this->authorize('viewAny', Campaign::class);

        $shopName = Setting::current()->shop_name;

        $html = match ($emailTemplate->key) {
            EmailTemplate::ABANDONED_CART => $this->previewAbandonedCart($shopName),
            EmailTemplate::LOW_STOCK_FAVORITE => $this->previewLowStock($shopName),
            EmailTemplate::PROMOTION => $this->previewPromotion($shopName),
            EmailTemplate::ACTIVE_PROMOTIONS => $this->previewActivePromotions($shopName),
            EmailTemplate::NEW_ARRIVALS => $this->previewNewArrivals($shopName),
        };

        return response($html);
    }

    /**
     * "Envoyer un email test" — l'administrateur reçoit exactement le même rendu que
     * "Prévisualiser" (mêmes données d'exemple), mais via un véritable envoi Mail plutôt qu'un
     * simple rendu HTML — section 8 du cahier des charges.
     */
    public function sendTest(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'test_email' => ['required', 'email'],
        ]);

        $mailable = $this->sampleMailable($emailTemplate);

        if (! $mailable) {
            return response()->json(['message' => "Impossible d'envoyer un test : aucun produit disponible dans le catalogue."], 422);
        }

        try {
            Mail::to($data['test_email'])->send($mailable);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => "Échec de l'envoi test : ".$e->getMessage()], 422);
        }

        return response()->json(['message' => 'Email test envoyé à '.$data['test_email'].'.']);
    }

    private function sampleMailable(EmailTemplate $emailTemplate): ?Mailable
    {
        /** @var User $admin */
        $admin = auth()->user();

        return match ($emailTemplate->key) {
            EmailTemplate::ABANDONED_CART => $this->sampleAbandonedCartMail($admin),
            EmailTemplate::LOW_STOCK_FAVORITE => $this->sampleLowStockMail($admin),
            EmailTemplate::PROMOTION => $this->samplePromotionMail($admin),
            EmailTemplate::ACTIVE_PROMOTIONS => $this->sampleActivePromotionsMail($admin),
            EmailTemplate::NEW_ARRIVALS => $this->sampleNewArrivalsMail($admin),
        };
    }

    private function sampleAbandonedCartMail(User $admin): ?AbandonedCartMail
    {
        $sampleProduct = Product::where('is_active', true)->first();

        if (! $sampleProduct) {
            return null;
        }

        $cart = new Cart(['items' => [['product_id' => $sampleProduct->id, 'variant_id' => null, 'quantity' => 1]]]);
        $cart->setRelation('user', $admin);

        return new AbandonedCartMail($cart);
    }

    private function sampleLowStockMail(User $admin): ?LowStockFavoriteMail
    {
        $sampleProduct = Product::with('images')->first();

        if (! $sampleProduct) {
            return null;
        }

        $products = collect([[
            'name' => $sampleProduct->name,
            'image' => $sampleProduct->images->first()?->url,
            'stock' => 3,
            'url' => route('products.show', $sampleProduct),
        ]]);

        return new LowStockFavoriteMail($admin, $products);
    }

    private function samplePromotionMail(User $admin): ?PromotionMail
    {
        $sampleProduct = Product::with('images')->first();

        if (! $sampleProduct) {
            return null;
        }

        $oldPrice = $sampleProduct->price;
        $newPrice = (int) round($oldPrice * 0.8);

        $products = collect([[
            'name' => $sampleProduct->name,
            'image' => $sampleProduct->images->first()?->url,
            'old_price' => $oldPrice,
            'new_price' => $newPrice,
            'discount_label' => '-20%',
            'url' => route('products.show', $sampleProduct),
        ]]);

        return new PromotionMail($admin, $products, EmailTemplate::PROMOTION);
    }

    private function sampleActivePromotionsMail(User $admin): ?PromotionMail
    {
        $realProducts = Product::where('is_promo', true)
            ->where('is_active', true)
            ->whereNotNull('old_price')
            ->with('images')
            ->take(3)
            ->get();

        if ($realProducts->isEmpty()) {
            return null;
        }

        $products = $realProducts->map(function (Product $p) {
            $discountPercent = $p->old_price > $p->price ? (int) round((($p->old_price - $p->price) / $p->old_price) * 100) : 0;

            return [
                'name' => $p->name,
                'image' => $p->images->first()?->url,
                'old_price' => $p->old_price,
                'new_price' => $p->price,
                'discount_label' => '-'.$discountPercent.'%',
                'url' => route('products.show', $p),
            ];
        })->values();

        return new PromotionMail($admin, $products, EmailTemplate::ACTIVE_PROMOTIONS);
    }

    private function sampleNewArrivalsMail(User $admin): ?NewArrivalsMail
    {
        $sampleProducts = Product::with(['images', 'category'])->take(2)->get();

        if ($sampleProducts->isEmpty()) {
            return null;
        }

        $products = $sampleProducts->map(fn (Product $p) => [
            'name' => $p->name,
            'image' => $p->images->first()?->url,
            'price' => $p->price,
            'description' => $p->description ? Str::limit($p->description, 90) : null,
            'category' => $p->category?->name,
            'url' => route('products.show', $p),
        ])->values();

        return new NewArrivalsMail($admin, $products);
    }

    private function previewAbandonedCart(string $shopName): string
    {
        $template = EmailTemplate::findByKey(EmailTemplate::ABANDONED_CART);
        $rendered = $template->render([
            'Prénom' => 'Aminata',
            'Nom' => 'Aminata Diallo',
            'NomBoutique' => $shopName,
            'LienPanier' => route('cart.index'),
        ]);

        $sampleProduct = \App\Models\Product::with('images')->first();

        return view('emails.abandoned-cart', [
            'user' => new \App\Models\User(['name' => 'Aminata Diallo']),
            'title' => $rendered['title'],
            'bodyText' => $rendered['content'],
            'buttonText' => $template->button_text ?? 'Reprendre ma commande',
            'items' => $sampleProduct ? collect([[
                'name' => $sampleProduct->name,
                'image' => $sampleProduct->images->first()?->url,
                'variant_label' => null,
                'quantity' => 1,
                'unit_price' => $sampleProduct->price,
                'subtotal' => $sampleProduct->price,
                'url' => '#',
            ]]) : collect(),
            'subtotal' => $sampleProduct->price ?? 0,
        ])->render();
    }

    private function previewLowStock(string $shopName): string
    {
        $template = EmailTemplate::findByKey(EmailTemplate::LOW_STOCK_FAVORITE);
        $sampleProduct = \App\Models\Product::with('images')->first();

        $rendered = $template->render([
            'Prénom' => 'Aminata',
            'Nom' => 'Aminata Diallo',
            'NomBoutique' => $shopName,
            'Produit' => $sampleProduct->name ?? 'Produit exemple',
            'Stock' => '3',
            'LienProduit' => '#',
        ]);

        return view('emails.low-stock-favorite', [
            'user' => new \App\Models\User(['name' => 'Aminata Diallo']),
            'title' => $rendered['title'],
            'bodyText' => $rendered['content'],
            'buttonText' => $template->button_text ?? 'Voir mes favoris',
            'products' => collect([[
                'name' => $sampleProduct->name ?? 'Produit exemple',
                'image' => $sampleProduct?->images->first()?->url,
                'stock' => 3,
                'url' => '#',
            ]]),
        ])->render();
    }

    private function previewPromotion(string $shopName): string
    {
        $template = EmailTemplate::findByKey(EmailTemplate::PROMOTION);
        $sampleProduct = \App\Models\Product::with('images')->first();
        $oldPrice = $sampleProduct->price ?? 10000;
        $newPrice = (int) round($oldPrice * 0.8);

        $rendered = $template->render([
            'Prénom' => 'Aminata',
            'Nom' => 'Aminata Diallo',
            'NomBoutique' => $shopName,
            'Produit' => $sampleProduct->name ?? 'Produit exemple',
            'Prix' => number_format($newPrice, 0, ',', ' ').' FCFA',
            'LienProduit' => '#',
        ]);

        return view('emails.promotion', [
            'user' => new \App\Models\User(['name' => 'Aminata Diallo']),
            'title' => $rendered['title'],
            'bodyText' => $rendered['content'],
            'buttonText' => $template->button_text ?? 'Voir le produit',
            'products' => collect([[
                'name' => $sampleProduct->name ?? 'Produit exemple',
                'image' => $sampleProduct?->images->first()?->url,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
                'discount_label' => '-20%',
                'url' => '#',
            ]]),
        ])->render();
    }

    private function previewActivePromotions(string $shopName): string
    {
        $template = EmailTemplate::findByKey(EmailTemplate::ACTIVE_PROMOTIONS);

        $realProducts = \App\Models\Product::where('is_promo', true)
            ->where('is_active', true)
            ->whereNotNull('old_price')
            ->with('images')
            ->take(3)
            ->get();

        $products = $realProducts->isNotEmpty()
            ? $realProducts->map(function ($p) {
                $discountPercent = $p->old_price > $p->price ? (int) round((($p->old_price - $p->price) / $p->old_price) * 100) : 0;

                return [
                    'name' => $p->name,
                    'image' => $p->images->first()?->url,
                    'old_price' => $p->old_price,
                    'new_price' => $p->price,
                    'discount_label' => '-'.$discountPercent.'%',
                    'url' => '#',
                ];
            })->values()
            : collect([[
                'name' => 'Produit exemple',
                'image' => null,
                'old_price' => 10000,
                'new_price' => 8000,
                'discount_label' => '-20%',
                'url' => '#',
            ]]);

        $rendered = $template->render([
            'Prénom' => 'Aminata',
            'Nom' => 'Aminata Diallo',
            'NomBoutique' => $shopName,
        ]);

        return view('emails.promotion', [
            'user' => new \App\Models\User(['name' => 'Aminata Diallo']),
            'title' => $rendered['title'],
            'bodyText' => $rendered['content'],
            'buttonText' => $template->button_text ?? 'Voir les promotions',
            'products' => $products,
        ])->render();
    }

    private function previewNewArrivals(string $shopName): string
    {
        $template = EmailTemplate::findByKey(EmailTemplate::NEW_ARRIVALS);
        $sampleProducts = \App\Models\Product::with(['images', 'category'])->take(2)->get();

        $rendered = $template->render([
            'Prénom' => 'Aminata',
            'Nom' => 'Aminata Diallo',
            'NomBoutique' => $shopName,
        ]);

        return view('emails.new-arrivals', [
            'user' => new \App\Models\User(['name' => 'Aminata Diallo']),
            'title' => $rendered['title'],
            'bodyText' => $rendered['content'],
            'buttonText' => $template->button_text ?? 'Découvrir',
            'products' => $sampleProducts->map(fn ($p) => [
                'name' => $p->name,
                'image' => $p->images->first()?->url,
                'price' => $p->price,
                'description' => $p->description ? \Illuminate\Support\Str::limit($p->description, 90) : null,
                'category' => $p->category?->name,
                'url' => '#',
            ])->values(),
        ])->render();
    }
}
