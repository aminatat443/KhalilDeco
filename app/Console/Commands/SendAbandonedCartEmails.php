<?php

namespace App\Console\Commands;

use App\Jobs\SendCampaignEmailJob;
use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Cart;
use App\Models\EmailTemplate;
use App\Models\Setting;
use Illuminate\Console\Command;

class SendAbandonedCartEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-abandoned-cart-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Détecte les paniers abandonnés depuis le délai configuré et met en file un email de relance';

    public function handle(): int
    {
        $settings = Setting::current();

        if (! $settings->abandoned_cart_enabled) {
            $this->info('Automatisation "panier abandonné" désactivée dans les paramètres — aucun envoi.');

            return self::SUCCESS;
        }

        $delayDays = max(1, (int) $settings->abandoned_cart_delay_days);

        $carts = Cart::query()
            ->whereNull('reminded_at')
            ->where('updated_at', '<=', now()->subDays($delayDays))
            ->with('user')
            ->get()
            ->filter(fn (Cart $cart) => $cart->user && ! empty($cart->items));

        if ($carts->isEmpty()) {
            $this->info('Aucun panier abandonné à relancer.');

            return self::SUCCESS;
        }

        $subjectTemplate = EmailTemplate::findByKey(EmailTemplate::ABANDONED_CART)->subject;

        $campaign = Campaign::create([
            'type' => 'reassort',
            'campaign_type' => Campaign::TYPE_ABANDONED_CART,
            'status' => Campaign::STATUS_PENDING,
            'subject' => Campaign::uniqueSubject($subjectTemplate, Campaign::TYPE_ABANDONED_CART),
            'subject_template' => $subjectTemplate,
            'recipients_count' => $carts->count(),
            'is_automatic' => true,
        ]);

        // Recalculé avec l'identifiant réel désormais connu : garantit l'unicité même si une
        // autre campagne du même type a été créée dans la même minute.
        $campaign->update(['subject' => Campaign::uniqueSubject($subjectTemplate, Campaign::TYPE_ABANDONED_CART, $campaign->id)]);

        foreach ($carts as $cart) {
            $send = CampaignSend::create([
                'campaign_id' => $campaign->id,
                'user_id' => $cart->user_id,
                'email' => $cart->user->email,
                'status' => CampaignSend::PENDING,
            ]);

            // Marqué tout de suite pour ne jamais relancer deux fois le même épisode d'abandon,
            // même si le job met du temps à passer en file (voir Cart::syncPersisted()).
            $cart->update(['reminded_at' => now()]);

            SendCampaignEmailJob::dispatch($send->id);
        }

        $this->info("{$carts->count()} email(s) de panier abandonné mis en file.");

        return self::SUCCESS;
    }
}
