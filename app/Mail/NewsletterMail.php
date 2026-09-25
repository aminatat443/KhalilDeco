<?php

namespace App\Mail;

use App\Models\CampaignSend;
use App\Models\EmailTemplate;
use App\Models\NewsletterSubscriber;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Volontairement pas `ShouldQueue` — voir la note dans AbandonedCartMail : la mise en file est
 * gérée par SendCampaignEmailJob, qui a besoin d'un envoi synchrone pour tracer les échecs.
 */
class NewsletterMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly CampaignSend $send)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: EmailTemplate::substitute($this->send->campaign->subject, $this->variables()));
    }

    public function content(): Content
    {
        $campaign = $this->send->campaign;
        $subscriber = NewsletterSubscriber::where('email', $this->send->email)->first();
        $variables = $this->variables();

        $products = ! empty($campaign->product_ids)
            ? Product::whereIn('id', $campaign->product_ids)->where('is_active', true)->with('images')->get()->map(fn (Product $product) => [
                'name' => $product->name,
                'image' => $product->images->first()?->url,
                'price' => $product->price,
                'url' => route('products.show', $product),
            ])->values()
            : collect();

        return new Content(
            view: 'emails.newsletter',
            with: [
                'title' => EmailTemplate::substitute($campaign->title, $variables),
                'bodyText' => EmailTemplate::substitute($campaign->message, $variables),
                'imageUrl' => $campaign->image_url,
                'buttonText' => $campaign->button_text,
                'buttonUrl' => $campaign->button_url,
                'products' => $products,
                'shopName' => $variables['NomBoutique'],
                'unsubscribeUrl' => $subscriber ? route('newsletter.unsubscribe', $subscriber->unsubscribe_token) : null,
                'campaignSendId' => $this->send->id,
            ],
        );
    }

    /**
     * [Prénom] / [Nom] / [NomBoutique] pour la campagne "Personnalisée" — même syntaxe que les
     * autres campagnes, mais appliquée à du texte libre plutôt qu'à un modèle enregistré. Les
     * abonnés invités (sans compte) reçoivent "Client" à défaut de prénom connu.
     *
     * @return array<string, string>
     */
    private function variables(): array
    {
        $name = $this->send->user->name ?? 'Client';

        return [
            'Prénom' => explode(' ', $name)[0],
            'Nom' => $name,
            'NomBoutique' => Setting::current()->shop_name,
        ];
    }
}
