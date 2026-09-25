<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\SecurityEvent;
use App\Services\SecurityMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function __construct(private readonly SecurityMonitor $security)
    {
    }

    /**
     * Centre de sécurité — surveillance applicative (IDS) et blocage d'IP (IPS). Ce n'est pas un
     * IDS/IPS réseau (type Snort/Suricata) : la portée se limite à ce que Laravel observe
     * (connexions, accès au back-office).
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', SecurityEvent::class);

        $events = SecurityEvent::with('user')
            ->when($request->filled('q'), fn ($q) => $q->where('ip_address', 'ilike', '%'.$request->input('q').'%'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.security.partials.events', ['events' => $events])->render(),
            ]);
        }

        $today = SecurityEvent::whereDate('created_at', today());

        return view('admin.security.index', [
            'alertsToday' => (clone $today)->where('severity', '!=', 'info')->count(),
            'suspiciousToday' => (clone $today)->whereIn('type', ['brute_force', 'unauthorized_access'])->count(),
            'blockedCount' => BlockedIp::active()->count(),
            'events' => $events,
            'blockedIps' => BlockedIp::active()->with('blockedBy')->latest()->paginate(15, ['*'], 'blocked_page')->withQueryString(),
        ]);
    }

    public function block(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', SecurityEvent::class);

        $data = $request->validate([
            'ip_address' => ['required', 'ip'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $this->security->block($data['ip_address'], $data['reason'], null, $request->user()->id);

        return back()->with('status', 'IP '.$data['ip_address'].' bloquée.');
    }

    public function unblock(BlockedIp $blockedIp): RedirectResponse
    {
        $this->authorize('viewAny', SecurityEvent::class);

        $this->security->unblock($blockedIp->ip_address);

        return back()->with('status', 'IP '.$blockedIp->ip_address.' débloquée.');
    }
}
