<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', ActivityLog::class);

        $logs = ActivityLog::with('user')
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->input('module')))
            ->when($request->filled('q'), fn ($q) => $q->where('description', 'ilike', '%'.$request->input('q').'%'))
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();

        $data = ['logs' => $logs];

        if ($request->ajax()) {
            return response()->json(['html' => view('admin.activity-logs.partials.list', $data)->render()]);
        }

        return view('admin.activity-logs.index', $data + [
            'modules' => ActivityLog::query()->distinct()->orderBy('module')->pluck('module'),
        ]);
    }
}
