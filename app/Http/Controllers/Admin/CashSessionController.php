<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CashSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CashSessionController extends Controller
{
    /**
     * Workflow de caisse (section 8 du cahier des charges multi-rôles) : ouverture avec un fonds
     * déclaré, opérations pendant la session (les vraies ventes/encaissements, déjà enregistrés
     * par ailleurs), clôture avec solde théorique calculé depuis les paiements espèces réels vs
     * montant compté — jamais une estimation.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', CashSession::class);

        $openSession = CashSession::where('user_id', $request->user()->id)->whereNull('closed_at')->first();

        $history = CashSession::with('user', 'closedBy')
            ->whereNotNull('closed_at')
            ->latest('closed_at')
            ->paginate(15);

        return view('admin.cash.index', [
            'openSession' => $openSession,
            'expectedNow' => $openSession?->expectedAmount(),
            'history' => $history,
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        $this->authorize('open', CashSession::class);

        if (CashSession::where('user_id', $request->user()->id)->whereNull('closed_at')->exists()) {
            throw ValidationException::withMessages(['opening_amount' => 'Vous avez déjà une session de caisse ouverte.']);
        }

        $data = $request->validate([
            'opening_amount' => ['required', 'integer', 'min:0'],
        ]);

        $session = CashSession::create([
            'user_id' => $request->user()->id,
            'opening_amount' => $data['opening_amount'],
            'opened_at' => now(),
        ]);

        ActivityLog::record('cash', 'opened', sprintf(
            '%s a ouvert la caisse avec un fonds de %d FCFA',
            $request->user()->name,
            $data['opening_amount'],
        ), $session);

        return redirect()->route('admin.cash.index')->with('status', 'Caisse ouverte.');
    }

    public function close(Request $request, CashSession $session): RedirectResponse
    {
        $this->authorize('close', $session);

        if (! $session->isOpen()) {
            throw ValidationException::withMessages(['closing_declared_amount' => 'Cette session est déjà clôturée.']);
        }

        $data = $request->validate([
            'closing_declared_amount' => ['required', 'integer', 'min:0'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $expected = $session->expectedAmount(now());
        $discrepancy = $data['closing_declared_amount'] - $expected;

        $session->update([
            'closing_declared_amount' => $data['closing_declared_amount'],
            'closing_expected_amount' => $expected,
            'discrepancy' => $discrepancy,
            'comment' => $data['comment'] ?? null,
            'closed_at' => now(),
            'closed_by' => $request->user()->id,
        ]);

        ActivityLog::record('cash', 'closed', sprintf(
            '%s a clôturé la caisse de %s — théorique %d FCFA, déclaré %d FCFA, écart %+d FCFA',
            $request->user()->name,
            $session->user->name,
            $expected,
            $data['closing_declared_amount'],
            $discrepancy,
        ), $session);

        return redirect()->route('admin.cash.index')->with('status', 'Caisse clôturée.');
    }
}
