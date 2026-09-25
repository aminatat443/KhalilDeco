<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\EmailTemplate;
use App\Models\Favorite;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Console\Command;

class SendPromotionFavoriteEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-promotion-favorite-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Prévient par email les clients dont un produit favori vient d'être marqué « En promotion »";

    public function handle(): int
    {
        $settings = Setting::current();

        if (! $settings->promotion_favorite_enabled) {
            $this->info('Automatisation "promotion sur favoris" désactivée dans les paramètres — aucun envoi.');

            return self::SUCCESS;
        }

        $favorites = Favorite::query()
            ->with(['user', 'product'])
            ->get();

        // Une promotion désactivée entre-temps peut de nouveau déclencher une alerte à l'avenir.
        $endedIds = $favorites
            ->filter(fn (Favorite $favorite) => $favorite->promo_notified_at !== null
                && $favorite->product
                && ! $this->isOnPromotion($favorite->product))
            ->pluck('id');

        if ($endedIds->isNotEmpty()) {
            Favorite::whereIn('id', $endedIds)->update(['promo_notified_at' => null]);
        }

        $toNotify = $favorites->filter(fn (Favorite $favorite) => $favorite->promo_notified_at === null
            && $favorite->user
            && $favorite->product
            && $this->isOnPromotion($favorite->product))
            ->groupBy('user_id');

        if ($toNotify->isEmpty()) {
            $this->info('Aucun favori en promotion à notifier.');

            return self::SUCCESS;
        }

        $subjectTemplate = EmailTemplate::findByKey(EmailTemplate::PROMOTION)->subject;

        $campaign = Campaign::create([
            'type' => 'promotion',
            'campaign_type' => Campaign::TYPE_PROMOTION_FAVORITE,
            'status' => Campaign::STATUS_PENDING,
            'subject' => Campaign::uniqueSubject($subjectTemplate, Campaign::TYPE_PROMOTION_FAVORITE),
            'subject_template' => $subjectTemplate,
            'recipients_count' => $toNotify->count(),
            'is_automatic' => true,
        ]);

        $campaign->update(['subject' => Campaign::uniqueSubject($subjectTemplate, Campaign::TYPE_PROMOTION_FAVORITE, $campaign->id)]);

        foreach ($toNotify as $userId => $userFavorites) {
            $user = $userFavorites->first()->user;

            $send = CampaignSend::create([
                'campaign_id' => $campaign->id,
                'user_id' => $userId,
                'email' => $user->email,
                'status' => CampaignSend::PENDING,
            ]);

            Favorite::whereIn('id', $userFavorites->pluck('id'))->update(['promo_notified_at' => now()]);

            SendCampaignEmailJob::dispatch($send->id);
        }

        $this->info("{$toNotify->count()} email(s) de promotion (favoris) mis en file.");

        return self::SUCCESS;
    }

    private function isOnPromotion(Product $product): bool
    {
        return $product->is_active
            && $product->is_promo
            && $product->old_price !== null
            && $product->old_price > $product->price;
    }
}
