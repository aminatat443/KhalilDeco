<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Registre de toutes les transactions (section 6 du cahier des charges multi-rôles) —
     * référence, commande associée, montant, moyen, statut, date.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        // La colonne de date pertinente dépend du statut regardé (un encaissement se date par
        // paid_at, un remboursement par updated_at...) — le tableau de bord qui a calculé le
        // chiffre affiché sur la carte sait laquelle utiliser et la transmet explicitement,
        // plutôt que de la deviner ici et risquer de diverger de la définition d'origine.
        $dateField = in_array($request->input('date_field'), ['created_at', 'paid_at', 'updated_at'], true)
            ? $request->input('date_field')
            : 'created_at';

        $payments = Payment::with('order')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('provider'), fn ($q) => $q->where('provider', $request->input('provider')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate($dateField, '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate($dateField, '<=', $request->input('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->input('q').'%';
                $q->where(fn ($sub) => $sub->where('transaction_id', 'ilike', $term)
                    ->orWhereHas('order', fn ($o) => $o->where('order_number', 'ilike', $term)->orWhere('customer_name', 'ilike', $term)));
            })
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json(['html' => view('admin.payments.partials.table', ['payments' => $payments])->render()]);
        }

        return view('admin.payments.index', ['payments' => $payments]);
    }

    /**
     * Enregistre un encaissement pour une commande — le caissier confirme avoir reçu l'argent
     * (espèces, mobile money, carte...), ce qui laisse une vraie trace dans `payments` au lieu de
     * seulement basculer le statut de la commande.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.$order->total],
            'provider' => ['required', 'in:cod,wave,orange_money,carte,especes'],
            'status' => ['required', 'in:success,pending'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = $order->payments()->create([
            'provider' => $data['provider'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'paid_at' => $data['status'] === 'success' ? now() : null,
        ]);

        if ($data['status'] === 'success') {
            $totalCollected = (int) $order->payments()->where('status', 'success')->sum('amount');
            if ($totalCollected >= $order->total && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
            }
        }

        ActivityLog::record('payments', 'recorded', sprintf(
            '%s a enregistré un paiement de %d FCFA (%s) sur la commande %s',
            $request->user()->name,
            $data['amount'],
            $data['provider'],
            $order->order_number,
        ), $payment);

        return back()->with('status', 'Paiement enregistré.');
    }

    /**
     * Confirme un paiement resté "en attente" (ex. mobile money initié mais pas encore confirmé).
     */
    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('verify', Payment::class);

        if ($payment->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Ce paiement n\'est pas en attente.']);
        }

        $data = $request->validate(['result' => ['required', 'in:success,failed']]);

        $payment->update([
            'status' => $data['result'],
            'paid_at' => $data['result'] === 'success' ? now() : null,
        ]);

        if ($data['result'] === 'success') {
            $order = $payment->order;
            $totalCollected = (int) $order->payments()->where('status', 'success')->sum('amount');
            if ($totalCollected >= $order->total && $order->payment_status !== 'paid') {
                $order->update(['payment_status' => 'paid']);
            }
        }

        ActivityLog::record('payments', 'verified', sprintf(
            '%s a %s le paiement #%d (%s)',
            $request->user()->name,
            $data['result'] === 'success' ? 'confirmé' : 'rejeté',
            $payment->id,
            $payment->order->order_number,
        ), $payment);

        return back()->with('status', 'Paiement mis à jour.');
    }

    /**
     * Rembourse un paiement déjà réussi — ne modifie pas automatiquement le statut de la commande
     * (un remboursement relève en général d'un retour, déjà traité par ce module dédié).
     */
    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('refund', Payment::class);

        if ($payment->status !== 'success') {
            throw ValidationException::withMessages(['status' => 'Seul un paiement réussi peut être remboursé.']);
        }

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $payment->update(['status' => 'refunded']);

        ActivityLog::record('payments', 'refunded', sprintf(
            '%s a remboursé le paiement de %d FCFA sur la commande %s%s',
            $request->user()->name,
            $payment->amount,
            $payment->order->order_number,
            $data['reason'] ? ' — '.$data['reason'] : '',
        ), $payment);

        return back()->with('status', 'Paiement remboursé.');
    }
}
