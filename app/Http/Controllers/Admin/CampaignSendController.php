<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CampaignSendController extends Controller
{
    /**
     * Historique des envois — une ligne par CAMPAGNE (jamais par destinataire individuel), avec
     * les statistiques agrégées déjà tenues à jour par Campaign::refreshStatus(). Le détail par
     * destinataire reste entièrement disponible, mais uniquement depuis "Voir les détails"
     * (admin.campaigns.show) — aucune donnée n'est supprimée, seule la présentation change.
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Campaign::class);

        $campaigns = Campaign::query()
            ->with('sender')
            ->when($request->filled('q'), fn ($q) => $q->where('subject', 'ilike', '%'.$request->input('q').'%'))
            ->when($request->filled('type'), fn ($q) => $q->where('campaign_type', $request->input('type')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('date'), function ($q) use ($request) {
                $date = Carbon::parse($request->input('date'));
                $column = $request->input('status') === 'sent' ? 'sent_at' : 'created_at';
                $q->whereDate($column, $date);
            })
            ->when($request->filled('month'), function ($q) use ($request) {
                $date = Carbon::createFromFormat('Y-m', $request->input('month'));
                $column = $request->input('status') === 'sent' ? 'sent_at' : 'created_at';
                $q->whereYear($column, $date->year)->whereMonth($column, $date->month);
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.campaigns.partials.sends-table', ['campaigns' => $campaigns])->render(),
            ]);
        }

        return view('admin.campaigns.sends', ['campaigns' => $campaigns]);
    }
}
