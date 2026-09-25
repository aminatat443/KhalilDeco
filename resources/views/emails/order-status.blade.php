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
            .mobile-stack { display: block !important; width: 100% !important; box-sizing: border-box !important; text-align: left !important; }
            .mobile-stack-right { text-align: left !important; padding-top: 6px !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f2f2f2; font-family: Helvetica, Arial, sans-serif; color:#1D302C;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2f2f2; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; max-width:600px; width:100%;">

                    {{-- Bandeau logo --}}
                    <tr>
                        <td class="mobile-px" style="background-color:#263F3A; padding-top:22px; padding-right:32px; padding-bottom:22px; padding-left:32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                                <td style="padding-right:12px;"><img src="{{ asset('images/email-logo-badge.png') }}" alt="Khalil Déco" width="36" height="36" style="display:block; border-radius:10px;"></td>
                                <td style="vertical-align:middle;"><span style="font-size:20px; font-weight:bold; color:#ffffff;">Khalil Déco</span></td>
                            </tr></table>
                        </td>
                    </tr>

                    {{-- Suivi de commande — même timeline que sur le site (compte client) --}}
                    @if($order->status === 'annulee')
                        <tr>
                            <td class="mobile-px" style="background-color:#fef2f2; padding-top:22px; padding-right:32px; padding-bottom:22px; padding-left:32px; text-align:center;">
                                <span style="font-size:26px;">✕</span>
                                <p style="margin:8px 0 0; font-size:16px; font-weight:bold; color:#b91c1c; text-transform:uppercase; letter-spacing:0.5px;">
                                    Commande annulée
                                </p>
                            </td>
                        </tr>
                    @else
                        @php
                            $steps = ['recue' => 'Reçue', 'confirmee' => 'Confirmée', 'en_preparation' => 'En préparation', 'expediee' => 'Expédiée', 'livree' => 'Livrée'];
                            $stepKeys = array_keys($steps);
                            $currentIndex = array_search($order->status, $stepKeys);
                            $currentIndex = $currentIndex === false ? 0 : $currentIndex;
                            $lastIndex = count($steps) - 1;
                        @endphp
                        <tr>
                            <td class="mobile-px" style="padding-top:26px; padding-right:24px; padding-bottom:6px; padding-left:24px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="table-layout:fixed;">
                                    <tr>
                                        @foreach($steps as $key => $label)
                                            @php
                                                $i = $loop->index;
                                                $reached = $i <= $currentIndex;
                                                $passed = $i < $currentIndex;
                                                $circleColor = $reached ? '#263F3A' : '#e0e0e0';
                                                $textColor = $reached ? '#1D302C' : '#999999';
                                                $leftLineColor = $i === 0 ? '#ffffff' : ($i <= $currentIndex ? '#263F3A' : '#e0e0e0');
                                                $rightLineColor = $i === $lastIndex ? '#ffffff' : ($i < $currentIndex ? '#263F3A' : '#e0e0e0');
                                            @endphp
                                            <td style="width:{{ round(100 / count($steps)) }}%; text-align:center; overflow-wrap:break-word;">
                                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                                                    <td style="width:50%; height:2px; background-color:{{ $leftLineColor }}; font-size:0; line-height:0;">&nbsp;</td>
                                                    <td style="width:22px;">
                                                        <table role="presentation" cellpadding="0" cellspacing="0" align="center"><tr>
                                                            <td width="22" height="22" style="width:22px; height:22px; min-width:22px; border-radius:11px; background-color:{{ $circleColor }}; color:#ffffff; font-size:10px; font-weight:bold; text-align:center; vertical-align:middle;">
                                                                {{ $passed ? '✓' : $i + 1 }}
                                                            </td>
                                                        </tr></table>
                                                    </td>
                                                    <td style="width:50%; height:2px; background-color:{{ $rightLineColor }}; font-size:0; line-height:0;">&nbsp;</td>
                                                </tr></table>
                                                <p style="margin:6px 0 0; font-size:7px; text-transform:uppercase; letter-spacing:0.2px; color:{{ $textColor }}; word-break:break-word; overflow-wrap:break-word;">{{ $label }}</p>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="mobile-px" style="padding-top:28px; padding-right:32px; padding-bottom:8px; padding-left:32px;">
                            <p style="font-size:15px; margin:0 0 4px;">Bonjour {{ $order->customer_name }},</p>
                            <p style="font-size:13px; color:#555555; margin:0;">
                                @if($order->status === 'confirmee')
                                    Votre commande a été confirmée et est en cours de préparation.
                                @elseif($order->status === 'en_preparation')
                                    Votre commande est en cours de préparation dans notre atelier.
                                @elseif($order->status === 'expediee')
                                    Bonne nouvelle : votre colis est en route !
                                @elseif($order->status === 'livree')
                                    Votre commande a été livrée. Un souci avec un article ? Vous pouvez demander un retour depuis votre espace client, dans les 7 jours suivant la commande.
                                @elseif($order->status === 'annulee')
                                    Cette commande a été annulée. Si un paiement avait déjà été effectué, il sera remboursé.
                                @else
                                    Le statut de votre commande <strong>{{ $order->order_number }}</strong> vient d'être mis à jour.
                                @endif
                            </p>
                        </td>
                    </tr>

                    {{-- Récapitulatif commande --}}
                    <tr>
                        <td class="mobile-px" style="padding-top:16px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f9f9f9; font-size:12px; color:#555555;">
                                <tr>
                                    <td class="mobile-stack" style="padding:14px 16px;">N° de commande<br><strong style="color:#1D302C; font-size:13px;">{{ $order->order_number }}</strong></td>
                                    <td class="mobile-stack" style="padding:14px 16px;">Date<br><strong style="color:#1D302C; font-size:13px;">{{ $order->created_at->format('d/m/Y') }}</strong></td>
                                    <td class="mobile-stack" style="padding:14px 16px;">Paiement<br><strong style="color:#1D302C; font-size:13px;">{{ $order->paymentMethodLabel() }}</strong></td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Articles --}}
                    <tr>
                        <td class="mobile-px" style="padding-top:24px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                            <p style="margin:0 0 12px; font-size:11px; font-weight:bold; text-transform:uppercase; letter-spacing:0.5px; color:#999999;">Votre commande</p>

                            @foreach($order->items as $item)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px; border:1px solid #eeeeee;">
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
                            @endforeach
                        </td>
                    </tr>

                    {{-- Totaux --}}
                    <tr>
                        <td class="mobile-px" style="padding-top:8px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13px; color:#555555;">
                                <tr>
                                    <td style="padding:2px 0;">Sous-total</td>
                                    <td style="padding:2px 0; text-align:right;">{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</td>
                                </tr>
                                <tr>
                                    <td style="padding:2px 0;">Livraison{{ $order->delivery_zone ? ' ('.$order->delivery_zone.')' : '' }}</td>
                                    <td style="padding:2px 0; text-align:right;">{{ $order->delivery_fee > 0 ? number_format($order->delivery_fee, 0, ',', ' ').' FCFA' : 'À confirmer sur WhatsApp' }}</td>
                                </tr>
                                @if($order->discount > 0)
                                    <tr>
                                        <td style="padding:2px 0; color:#C8A875;">Réduction</td>
                                        <td style="padding:2px 0; text-align:right; color:#C8A875;">-{{ number_format($order->discount, 0, ',', ' ') }} FCFA</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:10px 0 2px; border-top:1px solid #eeeeee; font-weight:bold; color:#1D302C;">Total</td>
                                    <td style="padding:10px 0 2px; border-top:1px solid #eeeeee; text-align:right; font-weight:bold; color:#1D302C;">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Livraison --}}
                    <tr>
                        <td class="mobile-px" style="padding-top:24px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                            <div style="padding:16px; background:#f9f9f9; font-size:12px; color:#555555;">
                                <strong style="color:#1D302C;">Livraison à :</strong> {{ $order->delivery_address }}{{ $order->delivery_quartier ? ', '.$order->delivery_quartier : '' }}, {{ $order->delivery_city }}, {{ $order->delivery_region }}
                            </div>
                        </td>
                    </tr>

                    {{-- CTA --}}
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
