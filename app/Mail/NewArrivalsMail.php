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
class NewArrivalsMail extends Mailable
{
    use SerializesModels;

    /**
     * @param  Collection<int, array{name: string, image: ?string, price: int, description: ?string, category: ?string, url: string}>  $products
     * @param  ?Campaign  $campaign  Permet à l'administrateur de personnaliser objet/titre/message à la création — utilise le modèle par défaut si non renseigné.
     */
    public function __construct(
        public readonly User $user,
        public readonly Collection $products,
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
            view: 'emails.new-arrivals',
            with: [
                'user' => $this->user,
                'title' => EmailTemplate::substitute($title, $variables),
                'bodyText' => EmailTemplate::substitute($message, $variables),
                'buttonText' => $this->campaign?->button_text ?: ($this->template()->button_text ?? 'Découvrir'),
                'products' => $this->products,
                'campaignSendId' => $this->campaignSendId,
            ],
        );
    }

    private function template(): EmailTemplate
    {
        return EmailTemplate::findByKey(EmailTemplate::NEW_ARRIVALS);
    }

    /**
     * [Prénom] / [Nom] / [NomBoutique] — appliqué à la fois au modèle par défaut et à une
     * éventuelle personnalisation saisie par l'administrateur à la création (les deux textes
     * peuvent contenir la même syntaxe `[Variable]`, notamment parce que le formulaire pré-remplit
     * les champs avec le modèle par défaut).
     *
     * @return array<string, string>
     */
    private function variables(): array
    {
        return [
            'Prénom' => explode(' ', $this->user->name)[0],
            'Nom' => $this->user->name,
            'NomBoutique' => Setting::current()->shop_name,
        ];
    }
}
