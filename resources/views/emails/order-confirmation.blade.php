<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Commande {{ $order->order_number }}</title>
    <style>
        @media only screen and (max-width: 480px) {
            .mobile-px { padding-left: 16px !important; padding-right: 16px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5; font-family: Helvetica, Arial, sans-serif; color:#1D302C;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; max-width:600px; width:100%;">

                    <tr>
                        <td class="mobile-px" style="background-color:#263F3A; padding-top:28px; padding-right:32px; padding-bottom:28px; padding-left:32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                                <td style="padding-right:12px;"><img src="{{ asset('images/email-logo-badge.png') }}" alt="Khalil Déco" width="36" height="36" style="display:block; border-radius:10px;"></td>
                                <td style="vertical-align:middle;"><span style="font-size:20px; font-weight:bold; color:#ffffff;">Khalil Déco</span></td>
                            </tr></table>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-px" style="padding:32px;">
                            <p style="font-size:16px; margin:0 0 8px;">Merci {{ $order->customer_name }} !</p>
                            <p style="font-size:14px; color:#555555; margin:0 0 8px;">
                                Votre commande <strong>{{ $order->order_number }}</strong> a bien été reçue et est en cours de traitement. Vous trouverez votre facture en pièce jointe.
                            </p>
                            @if($order->payment_method !== 'cod' && $order->payment_status === 'paid')
                                <p style="font-size:14px; color:#263F3A; font-weight:bold; margin:0 0 24px;">
                                    Votre paiement a été confirmé.
                                </p>
                            @else
                                <div style="margin:0 0 24px;"></div>
                            @endif

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; margin-bottom:16px;">
                                <thead>
                                    <tr>
                                        <td style="background:#f5f5f5; padding:8px 12px; font-size:11px; text-transform:uppercase; color:#777777;" colspan="2">Article</td>
                                        <td style="background:#f5f5f5; padding:8px 12px; font-size:11px; text-transform:uppercase; color:#777777; text-align:right;">Total</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->items as $item)
                                        <tr>
                                            <td width="56" style="padding:8px 0 8px 12px; border-bottom:1px solid #eeeeee;">
                                                @if($image = $item->product?->images->first()?->url)
                                                    <img src="{{ img_url($image, 112, 112) }}" alt="{{ $item->product_name }}" width="48" style="display:block; width:48px; height:48px; object-fit:cover;">
                                                @else
                                                    <div style="width:48px; height:48px; background:#f5f5f5;"></div>
                                                @endif
                                            </td>
                                            <td style="padding:8px 12px; font-size:13px; border-bottom:1px solid #eeeeee;">
                                                {{ $item->product_name }}
                                                @if($item->variant_label)
                                                    <br><span style="color:#999999; font-size:11px;">{{ $item->variant_label }}</span>
                                                @endif
                                                <br><span style="color:#999999; font-size:11px;">Qté : {{ $item->quantity }}</span>
                                            </td>
                                            <td style="padding:8px 12px; font-size:13px; text-align:right; border-bottom:1px solid #eeeeee;">
                                                {{ number_format($item->subtotal, 0, ',', ' ') }} FCFA
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; color:#555555;">
                                <tr>
                                    <td style="padding:2px 12px;">Sous-total</td>
                                    <td style="padding:2px 12px; text-align:right;">{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 12px;">Livraison{{ $order->delivery_zone ? ' ('.$order->delivery_zone.')' : '' }}</td>
                                    <td style="padding:2px 12px; text-align:right;">{{ $order->delivery_fee > 0 ? number_format($order->delivery_fee, 0, ',', ' ').' FCFA' : 'À confirmer sur WhatsApp' }}</td>
                                </tr>
                                @if($order->discount > 0)
                                    <tr>
                                        <td style="padding:2px 12px; color:#C8A875;">Réduction {{ $order->coupon?->code }}</td>
                                        <td style="padding:2px 12px; text-align:right; color:#C8A875;">-{{ number_format($order->discount, 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:10px 12px 2px; border-top:1px solid #eeeeee; font-weight:bold; color:#1D302C;">Total</td>
                                    <td style="padding:10px 12px 2px; border-top:1px solid #eeeeee; text-align:right; font-weight:bold; color:#1D302C;">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            </table>

                            <div style="margin-top:24px; padding:16px; background:#f5f5f5; font-size:13px; color:#555555;">
                                <strong>Livraison à :</strong> {{ $order->delivery_address }}, {{ $order->delivery_city }}, {{ $order->delivery_region }}<br>
                                <strong>Paiement :</strong> {{ $order->paymentMethodLabel() }}
                            </div>

                            <p style="font-size:12px; color:#999999; margin-top:24px;">
                                Une question ? Répondez simplement à cet email ou contactez-nous sur WhatsApp.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-px" style="padding:20px 32px; background-color:#f5f5f5; font-size:11px; color:#999999; text-align:center;">
                            © {{ date('Y') }} Khalil Déco — Décoration & Quincaillerie, Sénégal
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
