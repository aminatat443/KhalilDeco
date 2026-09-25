@extends('layouts.admin')

@section('title', 'Caisse')

@section('content')

<h1 class="text-2xl font-semibold text-secondary-shade dark:text-white">Caisse</h1>
<p class="mt-1 text-sm text-grey dark:text-white/40">Ouverture avec un fonds déclaré, clôture avec le solde théorique (espèces réellement encaissées) comparé au montant compté.</p>

@if($openSession)
    <div class="mt-8 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[0.1em] text-primary">
                    <span class="h-2 w-2 rounded-full bg-primary"></span>Session ouverte
                </span>
                <p class="mt-1 text-sm text-grey dark:text-white/40">Ouverte le {{ $openSession->opened_at->format('d/m/Y à H:i') }} par {{ $openSession->user->name }}</p>
            </div>
            <div class="text-right">
                <p class="text-xs uppercase tracking-[0.1em] text-grey dark:text-white/40">Solde théorique actuel</p>
                <p class="text-2xl font-semibold text-secondary-shade dark:text-white">{{ number_format($expectedNow, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>

        <p class="mt-3 text-xs text-grey dark:text-white/40">Fonds d'ouverture : {{ number_format($openSession->opening_amount, 0, ',', ' ') }} FCFA — calculé à partir des paiements « espèces » réels enregistrés depuis l'ouverture.</p>

        @can('close', $openSession)
            <form action="{{ route('admin.cash.close', $openSession) }}" method="POST" class="mt-6 grid grid-cols-1 gap-4 border-t border-secondary-shade/10 pt-6 dark:border-white/10 sm:grid-cols-[1fr_2fr_auto] sm:items-end">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Montant compté (FCFA)</label>
                    <input type="number" name="closing_declared_amount" min="0" required class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Commentaire (optionnel)</label>
                    <input type="text" name="comment" placeholder="Motif d'un éventuel écart..." class="w-full border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <button type="submit" class="bg-primary px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade" data-confirm="Clôturer la caisse avec ce montant ?">
                    Clôturer la caisse
                </button>
            </form>
        @endcan
    </div>
@else
    @can('open', App\Models\CashSession::class)
        <div class="mt-8 border border-secondary-shade/10 bg-white p-6 dark:border-white/10 dark:bg-[#16201f]">
            <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Ouvrir la caisse</h2>
            <form action="{{ route('admin.cash.open') }}" method="POST" class="mt-4 flex flex-wrap items-end gap-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-secondary-shade dark:text-white/70">Fonds de caisse (FCFA)</label>
                    <input type="number" name="opening_amount" min="0" required class="w-56 border border-secondary-shade/15 bg-white px-3.5 py-2 text-sm outline-none focus:border-primary focus:ring-2 focus:ring-primary/15 dark:border-white/10 dark:bg-white/5 dark:text-white">
                </div>
                <button type="submit" class="bg-primary px-6 py-2.5 text-xs font-semibold uppercase tracking-[0.15em] text-white shadow-sm transition hover:bg-primary-shade">
                    Ouvrir la caisse
                </button>
            </form>
        </div>
    @else
        <p class="mt-8 text-sm text-grey dark:text-white/40">Aucune session de caisse ouverte actuellement.</p>
    @endcan
@endif

<div class="mt-8">
    <h2 class="text-xs font-semibold uppercase tracking-[0.15em] text-secondary-shade dark:text-white/70">Historique des clôtures</h2>

    <div class="mt-4 space-y-3 lg:hidden">
        @forelse($history as $session)
            <div class="border border-secondary-shade/10 bg-white p-4 dark:border-white/10 dark:bg-[#16201f]">
                <p class="font-medium text-secondary-shade dark:text-white">{{ $session->user->name }}</p>
                <p class="text-xs text-grey dark:text-white/40">{{ $session->opened_at->format('d/m/Y H:i') }} → {{ $session->closed_at->format('d/m/Y H:i') }}</p>
                <dl class="mt-2 grid grid-cols-2 gap-2 text-xs">
                    <div><dt class="text-grey/60 dark:text-white/30">Théorique</dt><dd class="text-secondary-shade dark:text-white">{{ number_format($session->closing_expected_amount, 0, ',', ' ') }} FCFA</dd></div>
                    <div><dt class="text-grey/60 dark:text-white/30">Compté</dt><dd class="text-secondary-shade dark:text-white">{{ number_format($session->closing_declared_amount, 0, ',', ' ') }} FCFA</dd></div>
                    <div class="col-span-2"><dt class="text-grey/60 dark:text-white/30">Écart</dt><dd class="{{ $session->discrepancy == 0 ? 'text-secondary-shade dark:text-white' : ($session->discrepancy > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') }}">{{ $session->discrepancy > 0 ? '+' : '' }}{{ number_format($session->discrepancy, 0, ',', ' ') }} FCFA</dd></div>
                </dl>
                @if($session->comment)
                    <p class="mt-2 text-xs italic text-grey dark:text-white/40">{{ $session->comment }}</p>
                @endif
            </div>
        @empty
            <p class="border border-secondary-shade/10 bg-white p-6 text-center text-sm text-grey dark:border-white/10 dark:bg-[#16201f] dark:text-white/40">Aucune session clôturée pour le moment.</p>
        @endforelse
    </div>

    <div class="mt-4 hidden overflow-x-auto bg-white border border-secondary-shade/10 dark:bg-[#16201f] dark:border-white/10 lg:block">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-secondary-shade/10 text-left text-xs uppercase tracking-[0.1em] text-grey dark:border-white/10 dark:text-white/40">
                    <th class="px-6 py-4 font-medium">Utilisateur</th>
                    <th class="px-6 py-4 font-medium">Ouverte</th>
                    <th class="px-6 py-4 font-medium">Clôturée</th>
                    <th class="px-6 py-4 font-medium">Théorique</th>
                    <th class="px-6 py-4 font-medium">Compté</th>
                    <th class="px-6 py-4 font-medium">Écart</th>
                    <th class="px-6 py-4 font-medium">Commentaire</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary-shade/10 dark:divide-white/10">
                @forelse($history as $session)
                    <tr>
                        <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ $session->user->name }}</td>
                        <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $session->opened_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $session->closed_at->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ number_format($session->closing_expected_amount, 0, ',', ' ') }} FCFA</td>
                        <td class="px-6 py-4 text-secondary-shade dark:text-white">{{ number_format($session->closing_declared_amount, 0, ',', ' ') }} FCFA</td>
                        <td class="px-6 py-4 font-medium {{ $session->discrepancy == 0 ? 'text-secondary-shade dark:text-white' : ($session->discrepancy > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ $session->discrepancy > 0 ? '+' : '' }}{{ number_format($session->discrepancy, 0, ',', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-4 text-xs text-grey dark:text-white/40">{{ $session->comment ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-6 py-10 text-center text-sm text-grey dark:text-white/40">Aucune session clôturée pour le moment.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6" data-pagination>{{ $history->links() }}</div>
</div>

@endsection
