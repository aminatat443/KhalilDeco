<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Pro forma {{ $proforma->reference }}</title>
    <style>
        @page { size: A4; margin: 14mm 16mm 26mm; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1D302C; font-size: 13.5px; line-height: 1.45; }

        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; margin-bottom: 14px; }
        .header img.logo { max-height: 68px; max-width: 240px; }
        .header .shop-name { font-size: 24px; font-weight: bold; color: #263F3A; }
        .doc-meta { text-align: right; }
        .doc-meta .ref { font-size: 12px; color: #6B6B67; }
        .badge { display: inline-block; margin-top: 6px; padding: 4px 14px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 3px; color: #fff; background: #6B6B67; }
        .doc-meta p { margin: 4px 0 0; color: #555; font-size: 11.5px; }

        table.meta { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        table.meta td { vertical-align: top; width: 50%; padding: 12px 14px; }
        table.meta td.client { border: 1px solid #e2e2e2; }
        table.meta h3 { font-size: 10.5px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin: 0 0 8px; }
        table.meta p { margin: 0 0 3px; }
        table.meta p.name { font-weight: bold; color: #263F3A; }
        table.meta p.legal { color: #999; font-size: 10.5px; margin-top: 6px; }

        .doc-title { text-align: center; margin-bottom: 20px; }
        .doc-title h1 { display: inline-block; font-size: 22px; margin: 0 0 6px; color: #263F3A; text-transform: uppercase; letter-spacing: 2px; border-bottom: 2px solid #C8A875; padding-bottom: 6px; }

        .proforma-note { margin: -8px 0 20px; padding: 10px 14px; background: #F5F3EE; border-left: 3px solid #C8A875; font-size: 11px; color: #6B6B67; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.items th { background: #263F3A; color: #fff; text-align: left; padding: 10px 12px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        table.items td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 13px; }
        table.items td.num, table.items th.num { text-align: right; }

        table.totals { width: 300px; margin-left: auto; border-collapse: collapse; }
        table.totals td { padding: 6px 0; font-size: 13px; }
        table.totals td.label { color: #555; }
        table.totals td.value { text-align: right; }
        table.totals tr.total td { background: #263F3A; color: #fff; padding: 11px 14px; font-size: 15px; font-weight: bold; }
        table.totals tr.total td.label { color: #fff; }

        .notes { margin-top: 26px; font-size: 11px; line-height: 1.6; color: #555; }
        .notes h3 { font-size: 10.5px; text-transform: uppercase; letter-spacing: 1px; color: #999; margin: 0 0 6px; }

        .signoff { margin-top: 30px; font-size: 11.5px; color: #555; }

        .footer { position: fixed; bottom: -16mm; left: 0; right: 0; padding-top: 12px; border-top: 1px solid #eee; font-size: 10.5px; color: #999; text-align: center; }
        .footer p.thanks { font-size: 15px; font-style: italic; color: #263F3A; margin: 0 0 6px; }
    </style>
</head>
<body>

    <div class="header">
        @if($logo = $settings->invoiceLogoDataUri())
            <img class="logo" src="{{ $logo }}" alt="{{ $settings->shop_name }}">
        @else
            <div class="shop-name">{{ $settings->shop_name }}</div>
        @endif

        <div class="doc-meta">
            <p class="ref">Réf. : {{ $proforma->reference }}</p>
            <span class="badge">Pro forma</span>
            <p>Date du document : {{ $proforma->created_at->format('d/m/Y') }}</p>
        </div>
    </div>

    <table class="meta">
        <tr>
            <td>
                <h3>Émetteur</h3>
                <p class="name">{{ $settings->shop_name }}</p>
                @if($settings->shop_address)<p>{{ $settings->shop_address }}</p>@endif
                @if($settings->shop_phone)<p>Tél. : {{ $settings->shop_phone }}</p>@endif
                @if($settings->shop_email)<p>Email : {{ $settings->shop_email }}</p>@endif
                @if($settings->ninea || $settings->rccm)
                    <p class="legal">
                        @if($settings->ninea) NINEA : {{ $settings->ninea }} @endif
                        @if($settings->ninea && $settings->rccm) &nbsp;—&nbsp; @endif
                        @if($settings->rccm) RCCM : {{ $settings->rccm }} @endif
                    </p>
                @endif
            </td>
            <td class="client">
                <h3>Adressé à</h3>
                <p class="name">{{ $proforma->customer_name }}</p>
                @if($proforma->customer_address)<p>{{ $proforma->customer_address }}</p>@endif
                @if($proforma->customer_phone)<p>Tél. : {{ $proforma->customer_phone }}</p>@endif
                @if($proforma->customer_email)<p>Email : {{ $proforma->customer_email }}</p>@endif
            </td>
        </tr>
    </table>

    <div class="doc-title">
        <h1>Facture pro forma</h1>
    </div>

    <p class="proforma-note">
        Ce document est une facture pro forma établie à titre indicatif — il ne constitue pas une facture définitive
        et ne peut être utilisé comme justificatif comptable ou fiscal. Les montants sont susceptibles d'évoluer
        jusqu'à confirmation de la commande.
    </p>

    <table class="items">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="num">Qté</th>
                <th class="num">Prix unitaire</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($proforma->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                    <td class="num">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr class="total">
            <td class="label">Total estimé</td>
            <td class="value">{{ number_format($proforma->total(), 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    @if($proforma->notes)
        <div class="notes">
            <h3>Notes</h3>
            <p>{{ $proforma->notes }}</p>
        </div>
    @endif

    <div class="signoff">
        <p>Fait à Dakar, le {{ $proforma->created_at->format('d/m/Y à H:i') }}</p>
    </div>

    <div class="footer">
        <p class="thanks">Merci pour votre confiance !</p>
        {{ $settings->shop_name }} — Décoration &amp; Quincaillerie, livraison partout au Sénégal
    </div>

</body>
</html>
