<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Volontairement pas `ShouldQueue` — voir la note dans AbandonedCartMail : la mise en file est
 * gérée par SendCampaignEmailJob, qui a besoin d'un envoi synchrone pour tracer les échecs.
 */
class LowStockFavoriteMail extends Mailable
{
    use SerializesModels;

    /**
     * @param  Collection<int, array{name: string, image: ?string, stock: int, url: string}>  $products
     */
    public function __construct(public readonly User $user, public readonly Collection $products, public readonly ?int $campaignSendId = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->renderTemplate()['subject']);
    }

    public function content(): Content
    {
        $rendered = $this->renderTemplate();

        return new Content(
            view: 'emails.low-stock-favorite',
            with: [
                'user' => $this->user,
                'title' => $rendered['title'],
                'bodyText' => $rendered['content'],
                'buttonText' => $this->template()->button_text ?? 'Voir le produit',
                'products' => $this->products,
                'campaignSendId' => $this->campaignSendId,
            ],
        );
    }

    private function template(): EmailTemplate
    {
        return EmailTemplate::findByKey(EmailTemplate::LOW_STOCK_FAVORITE);
    }

    /**
     * @return array{subject: string, title: ?string, content: string}
     */
    private function renderTemplate(): array
    {
        $first = $this->products->first();

        return $this->template()->render([
            'Prénom' => explode(' ', $this->user->name)[0],
            'Nom' => $this->user->name,
            'NomBoutique' => Setting::current()->shop_name,
            'Produit' => $first['name'] ?? '',
            'Stock' => (string) ($first['stock'] ?? ''),
            'LienProduit' => $first['url'] ?? '',
        ]);
    }
}
