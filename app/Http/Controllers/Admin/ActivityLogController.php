<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ActivityLog::class);

        $logs = ActivityLog::with('user')
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->input('module')))
            ->when($request->filled('q'), fn ($q) => $q->where('description', 'ilike', '%'.$request->input('q').'%'))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        return view('admin.activity-logs.index', [
            'logs' => $logs,
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
        ]);
    }
}
