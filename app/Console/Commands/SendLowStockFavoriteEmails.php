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

class SendLowStockFavoriteEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-low-stock-favorite-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Prévient par email les clients dont un produit favori passe sous le seuil de stock configuré";

    public function handle(): int
    {
        $settings = Setting::current();

        if (! $settings->low_stock_favorite_enabled) {
            $this->info('Automatisation "favoris / stock faible" désactivée dans les paramètres — aucun envoi.');

            return self::SUCCESS;
        }

        $threshold = max(0, (int) $settings->low_stock_threshold);

        $favorites = Favorite::query()
            ->with(['user', 'product.variants'])
            ->get();

        // Un produit réapprovisionné peut de nouveau déclencher une alerte plus tard.
        $backInStockIds = $favorites
            ->filter(fn (Favorite $favorite) => $favorite->low_stock_notified_at !== null
                && $favorite->product
                && ! $this->isLowStock($favorite->product, $threshold))
            ->pluck('id');

        if ($backInStockIds->isNotEmpty()) {
            Favorite::whereIn('id', $backInStockIds)->update(['low_stock_notified_at' => null]);
        }

        $toNotify = $favorites->filter(fn (Favorite $favorite) => $favorite->low_stock_notified_at === null
            && $favorite->user
            && $favorite->product
            && $this->isLowStock($favorite->product, $threshold))
            ->groupBy('user_id');

        if ($toNotify->isEmpty()) {
            $this->info('Aucun favori en stock faible à notifier.');

            return self::SUCCESS;
        }

        $subjectTemplate = EmailTemplate::findByKey(EmailTemplate::LOW_STOCK_FAVORITE)->subject;

        $campaign = Campaign::create([
            'type' => 'reassort',
            'campaign_type' => Campaign::TYPE_LOW_STOCK_FAVORITE,
            'status' => Campaign::STATUS_PENDING,
            'subject' => Campaign::uniqueSubject($subjectTemplate, Campaign::TYPE_LOW_STOCK_FAVORITE),
            'subject_template' => $subjectTemplate,
            'recipients_count' => $toNotify->count(),
            'is_automatic' => true,
        ]);

        $campaign->update(['subject' => Campaign::uniqueSubject($subjectTemplate, Campaign::TYPE_LOW_STOCK_FAVORITE, $campaign->id)]);

        foreach ($toNotify as $userId => $userFavorites) {
            $user = $userFavorites->first()->user;

            $send = CampaignSend::create([
                'campaign_id' => $campaign->id,
                'user_id' => $userId,
                'email' => $user->email,
                'status' => CampaignSend::PENDING,
            ]);

            Favorite::whereIn('id', $userFavorites->pluck('id'))->update(['low_stock_notified_at' => now()]);

            SendCampaignEmailJob::dispatch($send->id);
        }

        $this->info("{$toNotify->count()} email(s) de stock faible (favoris) mis en file.");

        return self::SUCCESS;
    }

    private function isLowStock(Product $product, int $threshold): bool
    {
        $stock = $product->variants->isNotEmpty()
            ? (int) ($product->variants->where('stock', '>', 0)->min('stock') ?? 0)
            : (int) $product->stock;

        return $stock > 0 && $stock <= $threshold;
    }
}
