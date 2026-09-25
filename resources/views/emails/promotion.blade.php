<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>Promotion</title>
    <style>
        @media only screen and (max-width: 480px) {
            .mobile-px { padding-left: 16px !important; padding-right: 16px !important; }
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

                    <tr>
                        <td class="mobile-px" style="padding-top:28px; padding-right:32px; padding-bottom:8px; padding-left:32px;">
                            @if($title)
                                <p style="margin:0 0 6px; font-size:19px; font-weight:bold; color:#1D302C;">{{ $title }}</p>
                            @endif
                            <p style="margin:0; font-size:14px; line-height:1.6; color:#555555; white-space:pre-line;">{{ $bodyText }}</p>
                        </td>
                    </tr>

                    {{-- Produits --}}
                    <tr>
                        <td class="mobile-px" style="padding-top:20px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                @foreach($products as $product)
                                    <tr>
                                        <td style="padding-bottom:16px; border-bottom:1px solid #eeeeee;">
                                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td width="64" style="vertical-align:top;">
                                                        <a href="{{ email_track_click($campaignSendId ?? null, $product['url']) }}" style="text-decoration:none;">
                                                            @if($product['image'])
                                                                <img src="{{ img_url($product['image'], 120, 120) }}" alt="" width="56" height="56" style="display:block; object-fit:cover; background:#f5f3ee;">
                                                            @else
                                                                <div style="width:56px; height:56px; background:#f5f3ee;"></div>
                                                            @endif
                                                        </a>
                                                    </td>
                                                    <td style="vertical-align:top; padding-left:14px; padding-top:16px;">
                                                        <a href="{{ email_track_click($campaignSendId ?? null, $product['url']) }}" style="text-decoration:none; color:#1D302C; font-size:14px; font-weight:bold;">{{ $product['name'] }}</a>
                                                        <div style="font-size:12px; color:#C8A875; font-weight:bold; margin-top:2px; text-transform:uppercase; letter-spacing:0.4px;">{{ $product['discount_label'] }}</div>
                                                    </td>
                                                    <td style="vertical-align:top; padding-top:16px; text-align:right; white-space:nowrap;">
                                                        <div style="font-size:11px; color:#999999; text-decoration:line-through;">{{ number_format($product['old_price'], 0, ',', ' ') }} FCFA</div>
                                                        <div style="font-size:14px; font-weight:bold; color:#263F3A;">{{ number_format($product['new_price'], 0, ',', ' ') }} FCFA</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- CTA --}}
                    <tr>
                        <td class="mobile-px" style="padding-top:26px; padding-right:32px; padding-bottom:28px; padding-left:32px; text-align:center;">
                            <a href="{{ email_track_click($campaignSendId ?? null, route('search', ['promotions' => 1])) }}" style="display:inline-block; padding:14px 32px; background:#263F3A; color:#ffffff; font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:0.5px; text-decoration:none;">
                                {{ $buttonText }}
                            </a>
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
    @if($pixel = email_track_pixel($campaignSendId ?? null))
        <img src="{{ $pixel }}" width="1" height="1" alt="" style="display:block;border:0;">
    @endif
</body>
</html>
