<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\PaymentEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireStalePendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expire-stale-pending-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Annule les commandes en ligne restées en_attente_paiement au-delà du délai configuré (section 17 du cahier des charges commande/paiement)";

    public function handle(): int
    {
        $timeoutMinutes = (int) config('orders.payment_timeout_minutes');

        $orders = Order::where('status', 'en_attente_paiement')
            ->where('created_at', '<=', now()->subMinutes($timeoutMinutes))
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Aucune commande en attente de paiement expirée.');

            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            DB::transaction(function () use ($order, $timeoutMinutes) {
                // Aucun stock à libérer : jamais réservé/décrémenté avant confirmation du
                // paiement (voir OrderService::confirm()).
                $order->payments()->whereIn('status', ['pending', 'processing'])->update(['status' => 'cancelled']);

                $order->update([
                    'status' => 'annulee',
                    'admin_notes' => "Délai de paiement de {$timeoutMinutes} minutes dépassé — commande expirée automatiquement.",
                ]);

                PaymentEvent::record($order, 'order_expired', ['old_status' => 'en_attente_paiement']);
            });
        }

        $this->info("{$orders->count()} commande(s) en attente de paiement expirée(s).");

        return self::SUCCESS;
    }
}
