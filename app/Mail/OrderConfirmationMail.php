<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order)
    {
        $this->order->loadMissing('items.product.images', 'coupon');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre commande '.$this->order->order_number.' — Khalil Déco',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-confirmation',
            with: ['order' => $this->order],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $pdf = Pdf::loadView('pdf.invoice', ['order' => $this->order, 'settings' => Setting::current()])
            ->setPaper('a4')
            ->output();

        return [
            Attachment::fromData(fn () => $pdf, 'facture-'.$this->order->order_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
