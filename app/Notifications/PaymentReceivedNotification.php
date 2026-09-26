<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    public function __construct(public readonly Order $order, public readonly Payment $payment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'fa-circle-check',
            'tone' => 'green',
            'title' => 'Paiement reçu — commande '.$this->order->order_number,
            'message' => number_format($this->payment->amount, 0, ',', ' ').' FCFA par '.$this->order->paymentMethodLabel(),
            'url' => route('admin.orders.show', $this->order),
            'order_id' => $this->order->id,
        ];
    }
}
