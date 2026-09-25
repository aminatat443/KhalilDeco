<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Retour — {{ $order->order_number }}</title>
    <style>
        @media only screen and (max-width: 480px) {
            .mobile-px { padding-left: 16px !important; padding-right: 16px !important; }
            .mobile-stack { display: block !important; width: 100% !important; box-sizing: border-box !important; text-align: left !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f2f2f2; font-family: Helvetica, Arial, sans-serif; color:#1D302C;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f2f2; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; max-width:600px; width:100%;">

                    <tr>
                        <td class="mobile-px" style="background-color:#263F3A; padding-top:22px; padding-right:32px; padding-bottom:22px; padding-left:32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                                <td style="padding-right:12px;"><img src="{{ asset('images/email-logo-badge.png') }}" alt="Khalil Déco" width="36" height="36" style="display:block; border-radius:10px;"></td>
                                <td style="vertical-align:middle;"><span style="font-size:20px; font-weight:bold; color:#ffffff;">Khalil Déco</span></td>
                            </tr></table>
                        </td>
                    </tr>

                    @php
                        $statusMeta = [
                            'demandee' => ['icon' => '⏱', 'bg' => '#fffbeb', 'color' => '#b45309'],
                            'acceptee' => ['icon' => '✓', 'bg' => '#eff6ff', 'color' => '#1d4ed8'],
                            'article_recu' => ['icon' => '📦', 'bg' => '#f5f5f5', 'color' => '#555555'],
                            'refusee' => ['icon' => '✕', 'bg' => '#fef2f2', 'color' => '#b91c1c'],
                            'remboursee' => ['icon' => '✓', 'bg' => '#f0fdf4', 'color' => '#15803d'],
                        ][$return->status] ?? ['icon' => 'ℹ', 'bg' => '#f5f5f5', 'color' => '#555555'];
                    @endphp
                    <tr>
                        <td class="mobile-px" style="background-color:{{ $statusMeta['bg'] }}; padding-top:22px; padding-right:32px; padding-bottom:22px; padding-left:32px; text-align:center;">
                            <span style="font-size:26px;">{{ $statusMeta['icon'] }}</span>
                            <p style="margin:8px 0 0; font-size:16px; font-weight:bold; color:{{ $statusMeta['color'] }}; text-transform:uppercase; letter-spacing:0.5px;">
                                Retour {{ $statusLabel }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-px" style="padding-top:28px; padding-right:32px; padding-bottom:8px; padding-left:32px;">
                            <p style="font-size:15px; margin:0 0 4px;">Bonjour {{ $order->customer_name }},</p>
                            <p style="font-size:13px; color:#555555; margin:0;">
                                @if($return->status === 'demandee')
                                    Nous avons bien reçu votre demande de retour, elle est en cours d'examen.
                                @elseif($return->status === 'acceptee')
                                    Votre demande de retour a été acceptée. Merci de nous renvoyer l'article dans son état d'origine.
                                @elseif($return->status === 'article_recu')
                                    Nous avons bien reçu l'article retourné. Votre remboursement sera traité prochainement.
                                @elseif($return->status === 'refusee')
                                    Votre demande de retour n'a malheureusement pas pu être acceptée.
                                @elseif($return->status === 'remboursee')
                                    Votre remboursement a été effectué. Merci de votre confiance.
                                @endif
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-px" style="padding-top:16px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9; font-size:12px; color:#555555;">
                                <tr>
                                    <td class="mobile-stack" style="padding:14px 16px;">Commande<br><strong style="color:#1D302C; font-size:13px;">{{ $order->order_number }}</strong></td>
                                    <td class="mobile-stack" style="padding:14px 16px;">Motif<br><strong style="color:#1D302C; font-size:13px;">{{ ['taille' => 'Dimensions incorrectes', 'defaut' => 'Article défectueux', 'description' => 'Ne correspond pas à la description', 'autre' => 'Autre'][$return->reason] ?? $return->reason }}</strong></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-px" style="padding-top:24px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #eeeeee;">
                                <tr>
                                    <td width="72" style="padding:0;">
                                        @if($image = $item->product?->images->first()?->url)
                                            <img src="{{ img_url($image, 144, 144) }}" alt="{{ $item->product_name }}" width="72" style="display:block; width:72px; height:72px; object-fit:cover;">
                                        @else
                                            <div style="width:72px; height:72px; background:#f5f5f5;"></div>
                                        @endif
                                    </td>
                                    <td style="padding:10px 14px; vertical-align:middle;">
                                        <p style="margin:0 0 4px; font-size:13px; font-weight:bold; color:#1D302C;">{{ $item->product_name }}</p>
                                        @if($item->variant_label)
                                            <p style="margin:0 0 4px; font-size:11px; color:#999999;">{{ $item->variant_label }}</p>
                                        @endif
                                        <p style="margin:0; font-size:11px; color:#999999;">Qté : {{ $item->quantity }}</p>
                                    </td>
                                    <td style="padding:10px 14px; text-align:right; vertical-align:middle; white-space:nowrap;">
                                        <span style="font-size:13px; font-weight:bold; color:#1D302C;">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    @if($return->admin_note)
                        <tr>
                            <td class="mobile-px" style="padding-top:24px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                                <div style="padding:16px; background:#f9f9f9; font-size:12px; color:#555555;">
                                    <strong style="color:#1D302C;">Note :</strong> {{ $return->admin_note }}
                                </div>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="mobile-px" style="padding-top:28px; padding-right:32px; padding-bottom:28px; padding-left:32px; text-align:center;">
                            <a href="{{ route('account.orders.show', $order) }}" style="display:inline-block; padding:14px 32px; background:#263F3A; color:#ffffff; font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:0.5px; text-decoration:none;">
                                Voir ma commande
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-px" style="padding-top:0; padding-right:32px; padding-bottom:24px; padding-left:32px; text-align:center;">
                            <p style="font-size:11px; color:#999999; margin:0;">
                                Une question ? Répondez simplement à cet email ou contactez-nous sur WhatsApp.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-px" style="padding-top:20px; padding-right:32px; padding-bottom:20px; padding-left:32px; background-color:#f5f5f5; font-size:11px; color:#999999; text-align:center;">
                            © {{ date('Y') }} Khalil Déco — Décoration & Quincaillerie, Sénégal
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
