<?php

namespace App\Mail;

use App\Models\Cart;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Services\PromotionService;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Volontairement PAS `ShouldQueue` : cette Mailable est déjà envoyée depuis l'intérieur d'un job
 * mis en file (SendCampaignEmailJob), qui l'appelle avec Mail::send() de façon synchrone pour
 * pouvoir capturer une éventuelle erreur d'envoi et la consigner dans CampaignSend. La faire
 * implémenter ShouldQueue ferait que Mail::send() la remette elle-même en file (comportement
 * Laravel implicite), doublant la mise en file et empêchant de savoir si l'envoi a réellement
 * abouti.
 */
class AbandonedCartMail extends Mailable
{
    use SerializesModels;

    public function __construct(public readonly Cart $cart, public readonly ?int $campaignSendId = null)
    {
    }

    public function envelope(): Envelope
    {
        $rendered = $this->renderTemplate();

        return new Envelope(subject: $rendered['subject']);
    }

    public function content(): Content
    {
        $promotions = app(PromotionService::class);
        $rendered = $this->renderTemplate();

        $items = collect($this->cart->items)
            ->map(function (array $line) use ($promotions) {
                $product = Product::with('images')->find($line['product_id']);

                if (! $product) {
                    return null;
                }

                $variant = $line['variant_id'] ? ProductVariant::with('attributeValues.attribute')->find($line['variant_id']) : null;
                $unitPrice = $promotions->effectivePrice($product, $variant);

                return [
                    'name' => $product->name,
                    'image' => $product->images->first()?->url,
                    'variant_label' => $variant?->labelOrNull(),
                    'quantity' => $line['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $unitPrice * $line['quantity'],
                    'url' => route('products.show', $product),
                ];
            })
            ->filter()
            ->values();

        return new Content(
            view: 'emails.abandoned-cart',
            with: [
                'user' => $this->cart->user,
                'title' => $rendered['title'],
                'bodyText' => $rendered['content'],
                'buttonText' => $this->template()->button_text ?? 'Reprendre ma commande',
                'items' => $items,
                'subtotal' => $items->sum('subtotal'),
                'campaignSendId' => $this->campaignSendId,
            ],
        );
    }

    private function template(): EmailTemplate
    {
        return EmailTemplate::findByKey(EmailTemplate::ABANDONED_CART);
    }

    /**
     * @return array{subject: string, title: ?string, content: string}
     */
    private function renderTemplate(): array
    {
        $user = $this->cart->user;

        return $this->template()->render([
            'Prénom' => explode(' ', $user->name)[0],
            'Nom' => $user->name,
            'NomBoutique' => Setting::current()->shop_name,
            'LienPanier' => route('cart.index'),
        ]);
    }
}
