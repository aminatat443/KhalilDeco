<?php

namespace App\Mail;

use App\Models\Campaign;
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
class PromotionMail extends Mailable
{
    use SerializesModels;

    /**
     * @param  Collection<int, array{name: string, image: ?string, old_price: int, new_price: int, discount_label: string, url: string}>  $products
     * @param  string  $templateKey  EmailTemplate::PROMOTION (produit favori) ou ::ACTIVE_PROMOTIONS (catalogue)
     * @param  ?Campaign  $campaign  Permet à l'administrateur de personnaliser objet/titre/message à la création — utilise le modèle par défaut si non renseigné.
     */
    public function __construct(
        public readonly User $user,
        public readonly Collection $products,
        public readonly string $templateKey = EmailTemplate::PROMOTION,
        public readonly ?Campaign $campaign = null,
        public readonly ?int $campaignSendId = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->campaign?->subject ?: $this->template()->subject;

        return new Envelope(subject: EmailTemplate::substitute($subject, $this->variables()));
    }

    public function content(): Content
    {
        $variables = $this->variables();
        $title = $this->campaign?->title ?: $this->template()->title;
        $message = $this->campaign?->message ?: $this->template()->content;

        return new Content(
            view: 'emails.promotion',
            with: [
                'user' => $this->user,
                'title' => EmailTemplate::substitute($title, $variables),
                'bodyText' => EmailTemplate::substitute($message, $variables),
                'buttonText' => $this->campaign?->button_text ?: ($this->template()->button_text ?? 'Voir le produit'),
                'products' => $this->products,
                'campaignSendId' => $this->campaignSendId,
            ],
        );
    }

    private function template(): EmailTemplate
    {
        return EmailTemplate::findByKey($this->templateKey);
    }

    /**
     * [Prénom] / [Nom] / [NomBoutique] / [Produit] / [Prix] / [LienProduit] — appliqué au modèle
     * par défaut ET à une éventuelle personnalisation saisie par l'administrateur (le formulaire
     * pré-remplit les champs avec le modèle par défaut, qui peut donc lui aussi contenir ces
     * variables).
     *
     * @return array<string, string>
     */
    private function variables(): array
    {
        $first = $this->products->first();

        return [
            'Prénom' => explode(' ', $this->user->name)[0],
            'Nom' => $this->user->name,
            'NomBoutique' => Setting::current()->shop_name,
            'Produit' => $first['name'] ?? '',
            'Prix' => $first ? number_format($first['new_price'], 0, ',', ' ').' FCFA' : '',
            'LienProduit' => $first['url'] ?? '',
        ];
    }
}
