<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\NewsletterSubscriber;
use App\Services\CampaignCatalogResolver;
use App\Services\CampaignDispatcher;
use Illuminate\Console\Command;

class SendScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-scheduled-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Active les campagnes email programmées dont la date d'envoi est arrivée (section 9 « Nouvelle campagne »)";

    public function handle(CampaignCatalogResolver $catalog, CampaignDispatcher $dispatcher): int
    {
        $due = Campaign::where('status', Campaign::STATUS_SCHEDULED)
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($due as $campaign) {
            $subscribers = NewsletterSubscriber::whereNull('unsubscribed_at')->get();

            if ($subscribers->isEmpty()) {
                $campaign->update(['status' => Campaign::STATUS_FAILED]);
                $this->warn("Campagne #{$campaign->id} : aucun abonné newsletter actif au moment de l'envoi programmé.");

                continue;
            }

            // Les campagnes automatiques restent connectées au catalogue jusqu'au dernier moment :
            // les produits sont redétectés à l'échéance plutôt qu'au moment de la programmation.
            if (in_array($campaign->campaign_type, [Campaign::TYPE_NEW_ARRIVALS, Campaign::TYPE_ACTIVE_PROMOTIONS], true)) {
                $productIds = $campaign->campaign_type === Campaign::TYPE_NEW_ARRIVALS
                    ? $catalog->newArrivalsProducts()->pluck('id')->all()
                    : $catalog->activePromotionProducts()->pluck('id')->all();

                if (empty($productIds)) {
                    $campaign->update(['status' => Campaign::STATUS_FAILED]);
                    $this->warn("Campagne #{$campaign->id} : plus aucun produit disponible au moment de l'envoi programmé.");

                    continue;
                }

                $campaign->update(['product_ids' => $productIds]);
            }

            $dispatcher->activate($campaign, $subscribers);
            $this->info("Campagne #{$campaign->id} activée pour {$subscribers->count()} abonné(s).");
        }

        return self::SUCCESS;
    }
}
