<?php

namespace App\Mail;

use App\Models\ProductReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ProductReturn $return)
    {
        $this->return->loadMissing('orderItem.order', 'orderItem.product.images');
    }

    public function envelope(): Envelope
    {
        $label = ProductReturn::STATUS_LABELS[$this->return->status] ?? $this->return->status;

        return new Envelope(
            subject: 'Retour — '.$this->return->orderItem->order->order_number.' — '.$label.' — Khalil Déco',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.return-status',
            with: [
                'return' => $this->return,
                'order' => $this->return->orderItem->order,
                'item' => $this->return->orderItem,
                'statusLabel' => ProductReturn::STATUS_LABELS[$this->return->status] ?? $this->return->status,
            ],
        );
    }
}
