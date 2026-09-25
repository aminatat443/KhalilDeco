<?php

namespace App\Jobs;

use App\Mail\AbandonedCartMail;
use App\Mail\LowStockFavoriteMail;
use App\Mail\NewArrivalsMail;
use App\Mail\NewsletterMail;
use App\Mail\PromotionMail;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Cart;
use App\Models\EmailTemplate;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

/**
 * Unité d'envoi unique pour les 3 types de campagne — jamais d'envoi massif dans une requête
 * HTTP (section 15 du cahier des charges) : la commande/le contrôleur ne fait que préparer les
 * lignes `CampaignSend`, ce job s'occupe de l'envoi réel un par un via la queue. Revérifie les
 * conditions au moment de l'envoi plutôt que de faire confiance à l'état au moment de la mise en
 * file (le panier a pu être vidé, la commande finalisée, le stock être remonté entre-temps).
 */
class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $campaignSendId)
    {
    }

    public function handle(): void
    {
        $send = CampaignSend::with(['campaign', 'user'])->find($this->campaignSendId);

        if (! $send || $send->status !== CampaignSend::PENDING) {
            return;
        }

        try {
            $mailable = match ($send->campaign->campaign_type) {
                Campaign::TYPE_ABANDONED_CART => $this->abandonedCartMailable($send),
                Campaign::TYPE_LOW_STOCK_FAVORITE => $this->lowStockFavoriteMailable($send),
                Campaign::TYPE_PROMOTION_FAVORITE => $this->promotionFavoriteMailable($send),
                Campaign::TYPE_PROMOTION => $this->promotionMailable($send, EmailTemplate::PROMOTION),
                Campaign::TYPE_ACTIVE_PROMOTIONS => $this->promotionMailable($send, EmailTemplate::ACTIVE_PROMOTIONS),
                Campaign::TYPE_NEW_ARRIVALS => $this->newArrivalsMailable($send),
                Campaign::TYPE_NEWSLETTER => new NewsletterMail($send),
                default => throw new \RuntimeException('Type de campagne inconnu : '.$send->campaign->campaign_type),
            };

            if ($mailable === null) {
                // La condition n'est plus valable au moment de l'envoi (panier vidé, commande
                // finalisée, produit réapprovisionné...) — ce n'est pas un échec, juste un envoi
                // devenu inutile.
                $send->update(['status' => CampaignSend::FAILED, 'error_message' => "Condition d'envoi non confirmée au moment du traitement (panier/stock modifié entre-temps)."]);
            } else {
                Mail::to($send->email)->send($mailable);
                $send->markSent();
            }
        } catch (\Throwable $e) {
            $send->markFailed($e->getMessage());
            report($e);
        }

        $send->campaign->refreshStatus();
    }

    private function abandonedCartMailable(CampaignSend $send): ?AbandonedCartMail
    {
        if (! $send->user_id) {
            return null;
        }

        $cart = Cart::where('user_id', $send->user_id)->whereNotNull('items')->first();

        if (! $cart || empty($cart->items)) {
            return null;
        }

        return new AbandonedCartMail($cart, $send->id);
    }

    private function lowStockFavoriteMailable(CampaignSend $send): ?LowStockFavoriteMail
    {
        if (! $send->user_id || ! $send->user) {
            return null;
        }

        $threshold = Setting::current()->low_stock_threshold ?? 5;

        $products = Favorite::where('user_id', $send->user_id)
            ->with(['product.variants', 'product.images'])
            ->get()
            ->map(function (Favorite $favorite) use ($threshold) {
                if (! $favorite->product) {
                    return null;
                }

                $stock = $favorite->product->variants->isNotEmpty()
                    ? (int) ($favorite->product->variants->where('stock', '>', 0)->min('stock') ?? 0)
                    : (int) $favorite->product->stock;

                if ($stock <= 0 || $stock > $threshold) {
                    return null;
                }

                return [
                    'name' => $favorite->product->name,
                    'image' => $favorite->product->images->first()?->url,
                    'stock' => $stock,
                    'url' => route('products.show', $favorite->product),
                ];
            })
            ->filter()
            ->values();

        if ($products->isEmpty()) {
            return null;
        }

        return new LowStockFavoriteMail($send->user, $products, $send->id);
    }

    /**
     * "Promotion (favoris)" automatique — contrairement à promotionMailable() (liste fixée à la
     * création), regroupe TOUS les favoris actuellement en promotion de ce client au moment de
     * l'envoi, comme lowStockFavoriteMailable() : un même email peut couvrir plusieurs favoris.
     */
    private function promotionFavoriteMailable(CampaignSend $send): ?PromotionMail
    {
        if (! $send->user_id || ! $send->user) {
            return null;
        }

        $products = Favorite::where('user_id', $send->user_id)
            ->with(['product.images'])
            ->get()
            ->map(function (Favorite $favorite) {
                $product = $favorite->product;

                if (! $product || ! $product->is_active || ! $product->is_promo || ! $product->old_price || $product->old_price <= $product->price) {
                    return null;
                }

                $discountPercent = (int) round((($product->old_price - $product->price) / $product->old_price) * 100);

                return [
                    'name' => $product->name,
                    'image' => $product->images->first()?->url,
                    'old_price' => $product->old_price,
                    'new_price' => $product->price,
                    'discount_label' => '-'.$discountPercent.'%',
                    'url' => route('products.show', $product),
                ];
            })
            ->filter()
            ->values();

        if ($products->isEmpty()) {
            return null;
        }

        return new PromotionMail($send->user, $products, EmailTemplate::PROMOTION, campaignSendId: $send->id);
    }

    private function promotionMailable(CampaignSend $send, string $templateKey): ?PromotionMail
    {
        $productIds = $send->campaign->product_ids ?? [];

        if (empty($productIds)) {
            return null;
        }

        $products = Product::whereIn('id', $productIds)
            ->where('is_promo', true)
            ->where('is_active', true)
            ->whereNotNull('old_price')
            ->with('images')
            ->get()
            ->map(function (Product $product) {
                // Le statut "en promotion" a pu être désactivé entre-temps.
                if ($product->old_price <= $product->price) {
                    return null;
                }

                $discountPercent = (int) round((($product->old_price - $product->price) / $product->old_price) * 100);

                return [
                    'name' => $product->name,
                    'image' => $product->images->first()?->url,
                    'old_price' => $product->old_price,
                    'new_price' => $product->price,
                    'discount_label' => '-'.$discountPercent.'%',
                    'url' => route('products.show', $product),
                ];
            })
            ->filter()
            ->values();

        if ($products->isEmpty()) {
            return null;
        }

        return new PromotionMail($send->user ?? new User(['name' => 'Client']), $products, $templateKey, $send->campaign, $send->id);
    }

    private function newArrivalsMailable(CampaignSend $send): ?NewArrivalsMail
    {
        $productIds = $send->campaign->product_ids ?? [];

        if (empty($productIds)) {
            return null;
        }

        $products = Product::whereIn('id', $productIds)
            ->where('is_new', true)
            ->where('is_active', true)
            ->with(['images', 'category'])
            ->get()
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'image' => $product->images->first()?->url,
                'price' => $product->price,
                'description' => $product->description ? \Illuminate\Support\Str::limit($product->description, 90) : null,
                'category' => $product->category?->name,
                'url' => route('products.show', $product),
            ]);

        if ($products->isEmpty()) {
            return null;
        }

        return new NewArrivalsMail($send->user ?? new User(['name' => 'Client']), $products, $send->campaign, $send->id);
    }
}
