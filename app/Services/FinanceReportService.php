<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Support\DashboardPeriod;
use App\Support\MarginCalculator;

/**
 * Suite de rapports comptables (section 5 du cahier des charges multi-rôles) — chaque rapport
 * est une vraie requête sur les commandes/paiements/dépenses existants, jamais une donnée
 * inventée. Le grand livre est un livre de caisse simplifié (entrées/sorties chronologiques), pas
 * une comptabilité en partie double avec plan comptable — l'application n'a pas cette
 * infrastructure. L'analyse de marge par produit reste honnête sur les produits sans coût d'achat
 * renseigné plutôt que de les ignorer silencieusement.
 */
class FinanceReportService
{
    public const TYPES = [
        'sales' => 'Journal des ventes',
        'payments' => 'Journal des paiements',
        'receivables' => 'Créances clients',
        'invoices' => 'Analyse des factures',
        'cashflow' => 'Flux de trésorerie',
        'income-statement' => 'Compte de résultat interne',
        'refunds' => 'Rapport des remboursements',
        'by-method' => 'Paiements par méthode',
        'unpaid' => 'Commandes impayées',
        'ledger' => 'Grand livre (livre de caisse)',
        'margin-by-product' => 'Marge par produit',
    ];

    public function build(string $type, DashboardPeriod $period): array
    {
        return match ($type) {
            'payments' => $this->paymentsJournal($period),
            'receivables' => $this->receivables(),
            'invoices' => $this->invoiceAnalysis($period),
            'cashflow' => $this->cashFlow($period),
            'income-statement' => $this->incomeStatement($period),
            'refunds' => $this->refundsReport($period),
            'by-method' => $this->byMethod($period),
            'ledger' => $this->ledger($period),
            'margin-by-product' => $this->marginByProduct($period),
            'unpaid' => $this->unpaidOrders($period),
            default => $this->salesJournal($period),
        };
    }

    private function salesJournal(DashboardPeriod $period): array
    {
        $orders = Order::whereIn('status', Order::CONFIRMED_STATUSES)
            ->whereBetween('created_at', [$period->start, $period->end])
            ->orderBy('created_at')
            ->get();

        $labels = Order::PAYMENT_METHOD_LABELS;

        return [
            'title' => self::TYPES['sales'],
            'columns' => ['Date', 'Commande', 'Client', 'Moyen', 'Statut', 'Montant'],
            'rows' => $orders->map(fn (Order $o) => [
                $o->created_at->format('d/m/Y H:i'),
                $o->order_number,
                $o->customer_name,
                $labels[$o->payment_method] ?? $o->payment_method,
                Order::STATUS_LABELS[$o->status] ?? $o->status,
                number_format($o->total, 0, ',', ' ').' FCFA',
            ])->all(),
            'totals' => ['Nombre de ventes' => $orders->count(), 'Total' => number_format($orders->sum('total'), 0, ',', ' ').' FCFA'],
        ];
    }

    private function paymentsJournal(DashboardPeriod $period): array
    {
        $payments = Payment::with('order')
            ->whereBetween('created_at', [$period->start, $period->end])
            ->orderBy('created_at')
            ->get();

        $labels = Order::PAYMENT_METHOD_LABELS;
        $statusLabels = ['pending' => 'En attente', 'success' => 'Réussi', 'failed' => 'Échoué', 'refunded' => 'Remboursé'];

        return [
            'title' => self::TYPES['payments'],
            'columns' => ['Date', 'Commande', 'Moyen', 'Référence', 'Statut', 'Montant'],
            'rows' => $payments->map(fn (Payment $p) => [
                $p->created_at->format('d/m/Y H:i'),
                $p->order?->order_number ?? '—',
                $labels[$p->provider] ?? $p->provider,
                $p->transaction_id ?? '—',
                $statusLabels[$p->status] ?? $p->status,
                number_format($p->amount, 0, ',', ' ').' FCFA',
            ])->all(),
            'totals' => ['Nombre de paiements' => $payments->count(), 'Total' => number_format($payments->where('status', 'success')->sum('amount'), 0, ',', ' ').' FCFA (réussis)'],
        ];
    }

    /**
     * Photographie à l'instant présent (pas de période) : combien chaque client doit encore.
     */
    private function receivables(): array
    {
        $orders = Order::where('payment_status', '!=', 'paid')
            ->where('status', '!=', 'annulee')
            ->orderBy('created_at')
            ->get();

        $byClient = $orders->groupBy(fn (Order $o) => $o->customer_email ?: $o->customer_name);

        $rows = $byClient->map(function ($clientOrders, $key) {
            $oldest = $clientOrders->sortBy('created_at')->first();

            return [
                $clientOrders->first()->customer_name,
                $clientOrders->first()->customer_phone,
                $clientOrders->count(),
                number_format($clientOrders->sum('total'), 0, ',', ' ').' FCFA',
                (int) $oldest->created_at->diffInDays(now()).' jour(s)',
            ];
        })->sortByDesc(fn ($row) => (int) str_replace([' FCFA', ' '], '', $row[3]))->values()->all();

        return [
            'title' => self::TYPES['receivables'],
            'description' => 'Total dû par client, toutes commandes non payées et non annulées confondues — « jours » = ancienneté de la plus ancienne commande impayée de ce client.',
            'columns' => ['Client', 'Téléphone', 'Commandes impayées', 'Montant dû', 'Ancienneté'],
            'rows' => $rows,
            'totals' => ['Clients débiteurs' => $byClient->count(), 'Total des créances' => number_format($orders->sum('total'), 0, ',', ' ').' FCFA'],
        ];
    }

    private function invoiceAnalysis(DashboardPeriod $period): array
    {
        $orders = Order::whereBetween('created_at', [$period->start, $period->end])->get();
        $paid = $orders->where('payment_status', 'paid');

        return [
            'title' => self::TYPES['invoices'],
            'description' => 'Une facture est générée pour chaque commande — cette analyse porte donc sur les commandes de la période.',
            'columns' => ['Commande', 'Client', 'Date', 'Statut paiement', 'Montant'],
            'rows' => $orders->sortBy('created_at')->map(fn (Order $o) => [
                $o->order_number,
                $o->customer_name,
                $o->created_at->format('d/m/Y'),
                $o->payment_status,
                number_format($o->total, 0, ',', ' ').' FCFA',
            ])->values()->all(),
            'totals' => [
                'Factures émises' => $orders->count(),
                'Payées' => $paid->count(),
                'Montant total' => number_format($orders->sum('total'), 0, ',', ' ').' FCFA',
            ],
        ];
    }

    private function cashFlow(DashboardPeriod $period): array
    {
        $in = Payment::where('status', 'success')
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->groupBy('day')->pluck('total', 'day');

        $out = Payment::where('status', 'refunded')
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->selectRaw('DATE(updated_at) as day, SUM(amount) as total')
            ->groupBy('day')->pluck('total', 'day');

        $rows = [];
        $totalIn = 0;
        $totalOut = 0;
        for ($d = $period->start->copy()->startOfDay(); $d->lte($period->end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $dayIn = (int) ($in[$key] ?? 0);
            $dayOut = (int) ($out[$key] ?? 0);
            $totalIn += $dayIn;
            $totalOut += $dayOut;
            $rows[] = [$d->format('d/m/Y'), number_format($dayIn, 0, ',', ' ').' FCFA', number_format($dayOut, 0, ',', ' ').' FCFA', number_format($dayIn - $dayOut, 0, ',', ' ').' FCFA'];
        }

        return [
            'title' => self::TYPES['cashflow'],
            'columns' => ['Date', 'Encaissements', 'Remboursements', 'Net'],
            'rows' => $rows,
            'totals' => ['Encaissé' => number_format($totalIn, 0, ',', ' ').' FCFA', 'Remboursé' => number_format($totalOut, 0, ',', ' ').' FCFA', 'Net' => number_format($totalIn - $totalOut, 0, ',', ' ').' FCFA'],
        ];
    }

    private function incomeStatement(DashboardPeriod $period): array
    {
        $confirmed = Order::CONFIRMED_STATUSES;
        $gross = (int) Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->sum('total');
        $discounts = (int) Order::whereIn('status', $confirmed)->whereBetween('created_at', [$period->start, $period->end])->sum('discount');
        $refunds = (int) Payment::where('status', 'refunded')->whereBetween('updated_at', [$period->start, $period->end])->sum('amount');
        $net = $gross - $discounts - $refunds;

        $margin = MarginCalculator::compute($period->start, $period->end);
        $expenses = (int) Expense::whereBetween('expense_date', [$period->start, $period->end])->sum('amount');

        $rows = [
            ['Chiffre d\'affaires brut', number_format($gross, 0, ',', ' ').' FCFA'],
            ['- Remises accordées', '-'.number_format($discounts, 0, ',', ' ').' FCFA'],
            ['- Remboursements', '-'.number_format($refunds, 0, ',', ' ').' FCFA'],
            ['= Chiffre d\'affaires net encaissé', number_format($net, 0, ',', ' ').' FCFA'],
        ];

        if ($margin['available']) {
            $rows[] = ['- Coût des marchandises vendues (couverture '.$margin['coverageRatio'].'%)', '-'.number_format($margin['cogs'], 0, ',', ' ').' FCFA'];
            $rows[] = ['- Dépenses', '-'.number_format($expenses, 0, ',', ' ').' FCFA'];
            $rows[] = ['= Résultat net estimé', number_format($margin['margin'] - $expenses, 0, ',', ' ').' FCFA'];
        } else {
            $rows[] = ['- Coût des marchandises vendues', 'Donnée non disponible (coût d\'achat non renseigné)'];
            $rows[] = ['- Dépenses', '-'.number_format($expenses, 0, ',', ' ').' FCFA'];
            $rows[] = ['= Résultat net', 'Donnée non disponible'];
        }

        return [
            'title' => self::TYPES['income-statement'],
            'description' => $margin['available']
                ? "Le résultat net est une estimation : la couverture du coût d'achat n'est que de {$margin['coverageRatio']}% des articles vendus sur la période."
                : "Résultat net indisponible : aucun produit vendu sur cette période n'a de coût d'achat renseigné.",
            'columns' => ['Ligne', 'Montant'],
            'rows' => $rows,
            'totals' => [],
        ];
    }

    private function refundsReport(DashboardPeriod $period): array
    {
        $refunds = Payment::with('order')
            ->where('status', 'refunded')
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->orderBy('updated_at')
            ->get();

        return [
            'title' => self::TYPES['refunds'],
            'columns' => ['Date', 'Commande', 'Client', 'Moyen', 'Montant'],
            'rows' => $refunds->map(fn (Payment $p) => [
                $p->updated_at->format('d/m/Y H:i'),
                $p->order?->order_number ?? '—',
                $p->order?->customer_name ?? '—',
                Order::PAYMENT_METHOD_LABELS[$p->provider] ?? $p->provider,
                number_format($p->amount, 0, ',', ' ').' FCFA',
            ])->all(),
            'totals' => ['Remboursements' => $refunds->count(), 'Total' => number_format($refunds->sum('amount'), 0, ',', ' ').' FCFA'],
        ];
    }

    private function byMethod(DashboardPeriod $period): array
    {
        $rows = Order::whereIn('status', Order::CONFIRMED_STATUSES)
            ->whereBetween('created_at', [$period->start, $period->end])
            ->selectRaw('payment_method, count(*) as orders_count, sum(total) as total')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $labels = Order::PAYMENT_METHOD_LABELS;

        return [
            'title' => self::TYPES['by-method'],
            'columns' => ['Moyen de paiement', 'Commandes', 'Montant'],
            'rows' => $rows->map(fn ($r) => [$labels[$r->payment_method] ?? $r->payment_method, $r->orders_count, number_format($r->total, 0, ',', ' ').' FCFA'])->all(),
            'totals' => ['Total' => number_format($rows->sum('total'), 0, ',', ' ').' FCFA'],
        ];
    }

    private function unpaidOrders(DashboardPeriod $period): array
    {
        $orders = Order::where('payment_status', '!=', 'paid')
            ->where('status', '!=', 'annulee')
            ->whereBetween('created_at', [$period->start, $period->end])
            ->orderBy('created_at')
            ->get();

        return [
            'title' => self::TYPES['unpaid'],
            'columns' => ['Commande', 'Client', 'Date', 'Statut', 'Jours', 'Montant'],
            'rows' => $orders->map(fn (Order $o) => [
                $o->order_number,
                $o->customer_name,
                $o->created_at->format('d/m/Y'),
                Order::STATUS_LABELS[$o->status] ?? $o->status,
                (int) $o->created_at->diffInDays(now()),
                number_format($o->total, 0, ',', ' ').' FCFA',
            ])->all(),
            'totals' => ['Commandes impayées' => $orders->count(), 'Total' => number_format($orders->sum('total'), 0, ',', ' ').' FCFA'],
        ];
    }

    /**
     * Livre de caisse chronologique : chaque encaissement, remboursement et dépense réels,
     * dans l'ordre, avec un solde cumulé — pas une comptabilité en partie double (aucun plan
     * comptable dans l'application), mais un vrai historique des entrées et sorties d'argent.
     */
    private function ledger(DashboardPeriod $period): array
    {
        $entries = collect();

        Payment::with('order')->where('status', 'success')
            ->whereBetween('paid_at', [$period->start, $period->end])
            ->each(function (Payment $p) use ($entries) {
                $entries->push([
                    'date' => $p->paid_at,
                    'account' => 'Caisse',
                    'label' => 'Encaissement — commande '.($p->order?->order_number ?? '#'.$p->order_id),
                    'in' => (int) $p->amount,
                    'out' => 0,
                ]);
            });

        Payment::with('order')->where('status', 'refunded')
            ->whereBetween('updated_at', [$period->start, $period->end])
            ->each(function (Payment $p) use ($entries) {
                $entries->push([
                    'date' => $p->updated_at,
                    'account' => 'Caisse',
                    'label' => 'Remboursement — commande '.($p->order?->order_number ?? '#'.$p->order_id),
                    'in' => 0,
                    'out' => (int) $p->amount,
                ]);
            });

        Expense::whereBetween('expense_date', [$period->start, $period->end])
            ->each(function (Expense $e) use ($entries) {
                $entries->push([
                    'date' => $e->expense_date,
                    'account' => 'Charges',
                    'label' => (Expense::CATEGORIES[$e->category] ?? $e->category).' — '.$e->label,
                    'in' => 0,
                    'out' => (int) $e->amount,
                ]);
            });

        $entries = $entries->sortBy('date')->values();
        $balance = 0;
        $rows = $entries->map(function ($entry) use (&$balance) {
            $balance += $entry['in'] - $entry['out'];

            return [
                $entry['date']->format('d/m/Y H:i'),
                $entry['account'],
                $entry['label'],
                $entry['in'] > 0 ? number_format($entry['in'], 0, ',', ' ').' FCFA' : '',
                $entry['out'] > 0 ? number_format($entry['out'], 0, ',', ' ').' FCFA' : '',
                number_format($balance, 0, ',', ' ').' FCFA',
            ];
        })->all();

        return [
            'title' => self::TYPES['ledger'],
            'description' => "Livre de caisse simplifié (entrées/sorties réelles), pas une comptabilité en partie double — l'application ne gère pas de plan comptable.",
            'columns' => ['Date', 'Compte', 'Libellé', 'Entrée', 'Sortie', 'Solde cumulé'],
            'rows' => $rows,
            'totals' => [
                'Entrées' => number_format($entries->sum('in'), 0, ',', ' ').' FCFA',
                'Sorties' => number_format($entries->sum('out'), 0, ',', ' ').' FCFA',
                'Solde final' => number_format($balance, 0, ',', ' ').' FCFA',
            ],
        ];
    }

    /**
     * Marge réelle par produit — n'ignore jamais silencieusement un produit sans coût d'achat
     * renseigné : il apparaît quand même, avec "Non renseigné" sur les colonnes coût/marge.
     */
    private function marginByProduct(DashboardPeriod $period): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('orders.status', Order::CONFIRMED_STATUSES)
            ->whereBetween('orders.created_at', [$period->start, $period->end])
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.quantity) as qty, SUM(order_items.subtotal) as revenue, MAX(products.cost_price) as cost_price')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('revenue')
            ->get();

        return [
            'title' => self::TYPES['margin-by-product'],
            'description' => "Marge = revenu - (quantité × coût d'achat). \"Non renseigné\" tant que le coût d'achat n'a pas été ajouté sur la fiche produit.",
            'columns' => ['Produit', 'Quantité vendue', 'Chiffre d\'affaires', 'Coût total', 'Marge', 'Marge %'],
            'rows' => $rows->map(function ($row) {
                if ($row->cost_price === null) {
                    return [$row->product_name, $row->qty, number_format($row->revenue, 0, ',', ' ').' FCFA', 'Non renseigné', 'Non renseigné', '—'];
                }

                $cost = $row->qty * $row->cost_price;
                $margin = $row->revenue - $cost;
                $marginPct = $row->revenue > 0 ? round($margin / $row->revenue * 100) : 0;

                return [
                    $row->product_name,
                    $row->qty,
                    number_format($row->revenue, 0, ',', ' ').' FCFA',
                    number_format($cost, 0, ',', ' ').' FCFA',
                    number_format($margin, 0, ',', ' ').' FCFA',
                    $marginPct.'%',
                ];
            })->all(),
            'totals' => [
                'Produits vendus' => $rows->count(),
                'Coût connu sur' => $rows->whereNotNull('cost_price')->count().'/'.$rows->count(),
            ],
        ];
    }
}
