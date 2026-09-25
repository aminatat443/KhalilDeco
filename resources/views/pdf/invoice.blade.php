<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $order->order_number }}</title>
    <style>
        @page { size: A4; margin: 14mm 16mm 26mm; }
        body { font-family: 'Helvetica', Arial, sans-serif; color: #1D302C; font-size: 13.5px; line-height: 1.45; }

        .header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; margin-bottom: 14px; }
        .header img.logo { max-height: 68px; max-width: 240px; }
        .header .shop-name { font-size: 24px; font-weight: bold; color: #263F3A; }
        .doc-meta { text-align: right; }
        .doc-meta .ref { font-size: 12px; color: #6B6B67; }
        .badge { display: inline-block; margin-top: 6px; padding: 4px 14px; font-size: 11px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; border-radius: 3px; color: #fff; }
        .badge.paid { background: #2f855a; }
        .badge.pending { background: #C8A875; }
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

        .conditions { margin-top: 26px; font-size: 10.5px; line-height: 1.6; color: #777; }
        .conditions ol { margin: 6px 0 0; padding-left: 16px; }

        .signoff { margin-top: 30px; display: flex; justify-content: space-between; align-items: flex-end; }
        .signoff .place-date { font-size: 11.5px; color: #555; }
        .signoff img.stamp { max-height: 90px; max-width: 160px; }

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
            <p class="ref">Réf. : {{ $order->order_number }}</p>
            <span class="badge {{ $order->payment_status === 'paid' ? 'paid' : 'pending' }}">
                {{ $order->payment_status === 'paid' ? 'Payé' : 'En attente' }}
            </span>
            <p>Moyen de paiement : {{ $order->paymentMethodLabel() }}</p>
            <p>Date de la facture : {{ $order->created_at->format('d/m/Y') }}</p>
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
                <p class="name">{{ $order->customer_name }}</p>
                @if($order->is_in_store)
                    <p>Retrait en boutique</p>
                @else
                    <p>{{ $order->delivery_address }}{{ $order->delivery_quartier ? ', '.$order->delivery_quartier : '' }}</p>
                    <p>{{ $order->delivery_city }}, {{ $order->delivery_region }}</p>
                @endif
                <p>Tél. : {{ $order->customer_phone }}</p>
                <p>Email : {{ $order->customer_email }}</p>
            </td>
        </tr>
    </table>

    <div class="doc-title">
        <h1>Facture définitive</h1>
    </div>

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
            @foreach($order->items as $item)
                <tr>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variant_label)
                            <br><span style="color:#999;font-size:11px;">{{ $item->variant_label }}</span>
                        @endif
                    </td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                    <td class="num">{{ number_format($item->subtotal, 0, ',', ' ') }} FCFA</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td class="label">Sous-total</td>
            <td class="value">{{ number_format($order->subtotal, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td class="label">Livraison{{ $order->delivery_zone ? ' ('.$order->delivery_zone.')' : '' }}</td>
            <td class="value">{{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA</td>
        </tr>
        @if($order->discount > 0)
            <tr>
                <td class="label">Réduction {{ $order->coupon?->code }}</td>
                <td class="value">-{{ number_format($order->discount, 0, ',', ' ') }} FCFA</td>
            </tr>
        @endif
        <tr class="total">
            <td class="label">Total TTC</td>
            <td class="value">{{ number_format($order->total, 0, ',', ' ') }} FCFA</td>
        </tr>
    </table>

    <div class="conditions">
        Conditions de vente
        <ol>
            <li>Les articles peuvent être retournés dans un délai de 7 jours à compter de la date de la commande.</li>
            <li>L'article doit être retourné dans son état d'origine, non utilisé et dans son emballage d'origine.</li>
            <li>Le remboursement ou l'échange est effectué après vérification de l'article retourné par notre équipe.</li>
        </ol>
    </div>

    <div class="signoff">
        <p class="place-date">Fait à Dakar, le {{ $order->created_at->format('d/m/Y à H:i') }}</p>
        @if($signature = $settings->mediaDataUri('invoice_signature'))
            <img class="stamp" src="{{ $signature }}" alt="Signature et cachet">
        @endif
    </div>

    <div class="footer">
        <p class="thanks">Merci pour votre confiance !</p>
        {{ $settings->shop_name }} — Décoration &amp; Quincaillerie, livraison partout au Sénégal
    </div>

</body>
</html>
