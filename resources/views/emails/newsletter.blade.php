<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>{{ $title ?? $shopName }}</title>
    <style>
        @media only screen and (max-width: 480px) {
            .mobile-px { padding-left: 16px !important; padding-right: 16px !important; }
            .mobile-stack { display: block !important; width: 100% !important; box-sizing: border-box !important; padding-bottom: 16px !important; }
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
                                <td style="padding-right:12px;"><img src="{{ asset('images/email-logo-badge.png') }}" alt="{{ $shopName }}" width="36" height="36" style="display:block; border-radius:10px;"></td>
                                <td style="vertical-align:middle;"><span style="font-size:20px; font-weight:bold; color:#ffffff;">{{ $shopName }}</span></td>
                            </tr></table>
                        </td>
                    </tr>

                    @if($imageUrl)
                        <tr>
                            <td>
                                <img src="{{ $imageUrl }}" alt="" width="600" style="display:block; width:100%; max-width:600px; height:auto;">
                            </td>
                        </tr>
                    @endif

                    @if($title)
                        <tr>
                            <td class="mobile-px" style="padding-top:28px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                                <p style="margin:0; font-size:21px; font-weight:bold; color:#1D302C;">{{ $title }}</p>
                            </td>
                        </tr>
                    @endif

                    @if($bodyText)
                        <tr>
                            <td class="mobile-px" style="padding-top:14px; padding-right:32px; padding-bottom:0; padding-left:32px;">
                                <p style="margin:0; font-size:14px; line-height:1.7; color:#555555; white-space:pre-line;">{{ $bodyText }}</p>
                            </td>
                        </tr>
                    @endif

                    @if(isset($products) && $products->isNotEmpty())
                        <tr>
                            <td class="mobile-px" style="padding-top:22px; padding-right:24px; padding-bottom:8px; padding-left:24px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        @foreach($products as $product)
                                            <td class="mobile-stack" width="50%" valign="top" style="padding:8px;">
                                                <a href="{{ email_track_click($campaignSendId ?? null, $product['url']) }}" style="text-decoration:none; color:#1D302C;">
                                                    @if($product['image'])
                                                        <img src="{{ img_url($product['image'], 260, 260) }}" alt="" width="100%" style="display:block; width:100%; height:auto; background:#f5f3ee;">
                                                    @else
                                                        <div style="width:100%; padding-bottom:100%; background:#f5f3ee;"></div>
                                                    @endif
                                                    <div style="margin-top:8px; font-size:13px; font-weight:bold;">{{ $product['name'] }}</div>
                                                    <div style="margin-top:2px; font-size:13px; color:#263F3A;">{{ number_format($product['price'], 0, ',', ' ') }} FCFA</div>
                                                </a>
                                            </td>
                                            @if($loop->iteration % 2 === 0 && ! $loop->last)
                                                </tr><tr>
                                            @endif
                                        @endforeach
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    @if($buttonText && $buttonUrl)
                        <tr>
                            <td class="mobile-px" style="padding-top:26px; padding-right:32px; padding-bottom:28px; padding-left:32px; text-align:center;">
                                <a href="{{ email_track_click($campaignSendId ?? null, $buttonUrl) }}" style="display:inline-block; padding:14px 32px; background:#263F3A; color:#ffffff; font-size:12px; font-weight:bold; text-transform:uppercase; letter-spacing:0.5px; text-decoration:none;">
                                    {{ $buttonText }}
                                </a>
                            </td>
                        </tr>
                    @else
                        <tr><td style="padding-bottom:24px;"></td></tr>
                    @endif

                    <tr>
                        <td class="mobile-px" style="padding-top:20px; padding-right:32px; padding-bottom:20px; padding-left:32px; background-color:#f5f5f5; font-size:11px; color:#999999; text-align:center;">
                            © {{ date('Y') }} {{ $shopName }} — Décoration & Quincaillerie, Sénégal
                            @if($unsubscribeUrl)
                                <br><a href="{{ $unsubscribeUrl }}" style="color:#999999; text-decoration:underline;">Se désinscrire de la newsletter</a>
                            @endif
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
