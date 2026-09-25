<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $campaign->subject }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f5f5; font-family: Helvetica, Arial, sans-serif; color:#1D302C;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5; padding:32px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; max-width:600px; width:100%;">

                    <tr>
                        <td style="background-color:#263F3A; padding:28px 32px;">
                            <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                                <td style="padding-right:12px;"><img src="{{ asset('images/email-logo-badge.png') }}" alt="Khalil Déco" width="36" height="36" style="display:block; border-radius:10px;"></td>
                                <td style="vertical-align:middle;"><span style="font-size:20px; font-weight:bold; color:#ffffff;">Khalil Déco</span></td>
                            </tr></table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:32px;">
                            <span style="display:inline-block; margin-bottom:14px; padding:4px 12px; background:#fdece7; color:#C8A875; font-size:10px; font-weight:bold; text-transform:uppercase; letter-spacing:0.5px;">
                                {{ $typeLabel }}
                            </span>

                            <p style="font-size:16px; margin:0 0 8px;">Bonjour {{ $recipientName }},</p>

                            @if($campaign->message)
                                <p style="font-size:14px; color:#555555; margin:0 0 24px; white-space:pre-line;">{{ $campaign->message }}</p>
                            @endif

                            @foreach($products as $product)
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px; border:1px solid #eeeeee;">
                                    <tr>
                                        <td width="110" style="padding:0;">
                                            @if($image = $product->images->first()?->url)
                                                <img src="{{ img_url($image, 220, 220) }}" alt="{{ $product->name }}" width="110" style="display:block; width:110px; height:110px; object-fit:cover;">
                                            @endif
                                        </td>
                                        <td style="padding:14px 18px; vertical-align:middle;">
                                            <p style="margin:0 0 6px; font-size:14px; font-weight:bold; color:#1D302C;">{{ $product->name }}</p>
                                            <p style="margin:0 0 12px; font-size:14px; color:#C8A875;">{{ number_format($product->price, 0, ',', ' ') }} FCFA</p>
                                            <a href="{{ route('products.show', $product) }}" style="display:inline-block; padding:8px 16px; background:#263F3A; color:#ffffff; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; text-decoration:none;">Voir le produit</a>
                                        </td>
                                    </tr>
                                </table>
                            @endforeach

                            <p style="font-size:12px; color:#999999; margin-top:24px;">
                                Une question ? Répondez simplement à cet email ou contactez-nous sur WhatsApp.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px 32px; background-color:#f5f5f5; font-size:11px; color:#999999; text-align:center;">
                            © {{ date('Y') }} Khalil Déco — Décoration & Quincaillerie, Sénégal
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
