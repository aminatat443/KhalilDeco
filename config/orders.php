<?php

return [
    // Durée au-delà de laquelle une commande en ligne restée "en_attente_paiement" est
    // considérée abandonnée (section 17 du cahier des charges commande/paiement).
    'payment_timeout_minutes' => (int) env('ORDER_PAYMENT_TIMEOUT_MINUTES', 60),
];
