<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order)
    {
        $this->order->loadMissing('items.product.images');
    }

    public function envelope(): Envelope
    {
        $label = Order::STATUS_LABELS[$this->order->status] ?? $this->order->status;

        return new Envelope(
            subject: 'Commande '.$this->order->order_number.' — '.$label.' — Khalil Déco',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status',
            with: [
                'order' => $this->order,
                'statusLabel' => Order::STATUS_LABELS[$this->order->status] ?? $this->order->status,
            ],
        );
    }
}
