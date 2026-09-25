<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FinanceReportService;
use App\Support\DashboardPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FinanceReportController extends Controller
{
    public function index(Request $request, FinanceReportService $service): View|Response|JsonResponse
    {
        $type = array_key_exists((string) $request->input('type'), FinanceReportService::TYPES) ? $request->input('type') : 'sales';
        $period = DashboardPeriod::resolve($request->input('period'), $request->input('from'), $request->input('to'));

        $report = $service->build($type, $period);

        if ($request->input('format') === 'csv') {
            $user = $request->user();
            abort_unless($user->role_id === null || $user->hasPermission('reports.export'), 403);

            return $this->toCsv($report);
        }

        if ($request->ajax()) {
            return response()->json(['html' => view('admin.finances.partials.report-result', ['report' => $report, 'period' => $period])->render()]);
        }

        return view('admin.finances.reports', [
            'report' => $report,
            'type' => $type,
            'period' => $period,
            'types' => FinanceReportService::TYPES,
        ]);
    }

    private function toCsv(array $report): Response
    {
        $lines = [];
        $lines[] = implode(';', $report['columns']);
        foreach ($report['rows'] as $row) {
            $lines[] = implode(';', array_map(fn ($cell) => str_replace(';', ',', (string) $cell), $row));
        }

        $filename = \Illuminate\Support\Str::slug($report['title']).'-'.now()->format('Y-m-d').'.csv';

        return response("\xEF\xBB\xBF".implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
