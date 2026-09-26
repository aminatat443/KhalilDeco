<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class PaymentCancelledNotification extends Notification
{
    public function __construct(public readonly Order $order)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'icon' => 'fa-ban',
            'tone' => 'red',
            'title' => 'Paiement annulé — commande '.$this->order->order_number,
            'message' => $this->order->customer_name.' — '.number_format($this->order->total, 0, ',', ' ').' FCFA ('.$this->order->paymentMethodLabel().')',
            'url' => route('admin.orders.show', $this->order),
            'order_id' => $this->order->id,
        ];
    }
}
