<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nécessite qu'un vrai ordonnanceur tourne côté serveur (cron `* * * * * php artisan schedule:run`
// ou un `php artisan schedule:work` en tâche de fond) — sans ça, ces commandes existent mais ne
// s'exécutent jamais automatiquement. Vérifiée chaque heure plutôt qu'une fois par jour : les
// commandes ne notifient jamais deux fois le même client (reminded_at / low_stock_notified_at),
// donc une fréquence plus élevée ne fait que réduire le délai avant la première alerte, sans
// risque de spam. Le délai réel avant relance/seuil de stock se règle toujours dans Configuration.
Schedule::command('app:send-abandoned-cart-emails')->hourly();
Schedule::command('app:send-low-stock-favorite-emails')->hourly();
Schedule::command('app:send-promotion-favorite-emails')->hourly();

// Campagnes programmées (section 9 « Nouvelle campagne ») — vérifiée chaque minute pour respecter
// l'heure choisie par l'administrateur, sous la même contrainte d'ordonnanceur que ci-dessus.
Schedule::command('app:send-scheduled-campaigns')->everyMinute();
