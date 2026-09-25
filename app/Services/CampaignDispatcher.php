<?php

namespace App\Services;

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignSend;
use Illuminate\Support\Collection;

/**
 * Fait passer une campagne (créée immédiatement ou programmée puis arrivée à échéance) de
 * "prête" à "en file d'envoi" : crée une ligne `CampaignSend` par abonné et met chaque envoi en
 * file via `SendCampaignEmailJob` — jamais d'envoi direct dans une requête HTTP.
 */
class CampaignDispatcher
{
    /**
     * @param  Collection<int, \App\Models\NewsletterSubscriber>  $subscribers
     */
    public function activate(Campaign $campaign, Collection $subscribers): void
    {
        $campaign->update([
            'status' => Campaign::STATUS_PENDING,
            'recipients_count' => $subscribers->count(),
            // Calculé ici plutôt qu'à la création : pour une campagne programmée, l'objet doit
            // porter la date du véritable envoi, pas celle de sa mise en place. Tous les
            // destinataires de CETTE campagne reçoivent le même objet, figé une seule fois ici.
            'subject' => $campaign->subject_template
                ? Campaign::uniqueSubject($campaign->subject_template, $campaign->campaign_type, $campaign->id)
                : $campaign->subject,
        ]);

        foreach ($subscribers as $subscriber) {
            $send = CampaignSend::create([
                'campaign_id' => $campaign->id,
                'user_id' => $subscriber->user_id,
                'email' => $subscriber->email,
                'status' => CampaignSend::PENDING,
            ]);

            SendCampaignEmailJob::dispatch($send->id);
        }
    }
}
