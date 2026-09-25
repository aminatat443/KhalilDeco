<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Expense;
use App\Support\DashboardPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Expense::class);

        $period = DashboardPeriod::resolve($request->input('period'), $request->input('from'), $request->input('to'));

        $expenses = Expense::with('user')
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->whereBetween('expense_date', [$period->start, $period->end])
            ->orderByDesc('expense_date')
            ->paginate(20)
            ->withQueryString();

        $byCategory = Expense::whereBetween('expense_date', [$period->start, $period->end])
            ->selectRaw('category, sum(amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $data = [
            'expenses' => $expenses,
            'byCategory' => $byCategory,
            'total' => $byCategory->sum('total'),
            'period' => $period,
        ];

        if ($request->ajax()) {
            return response()->json(['html' => view('admin.expenses.partials.list', $data)->render()]);
        }

        return view('admin.expenses.index', $data);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $data = $request->validate([
            'category' => ['required', 'in:'.implode(',', array_keys(Expense::CATEGORIES))],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $expense = Expense::create($data + ['user_id' => $request->user()->id]);

        ActivityLog::record('finances', 'expense_created', sprintf(
            '%s a enregistré une dépense de %d FCFA (%s — %s)',
            $request->user()->name,
            $data['amount'],
            Expense::CATEGORIES[$data['category']],
            $data['label'],
        ), $expense);

        return back()->with('status', 'Dépense enregistrée.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        ActivityLog::record('finances', 'expense_deleted', sprintf(
            '%s a supprimé une dépense de %d FCFA (%s)',
            auth()->user()->name,
            $expense->amount,
            $expense->label,
        ));

        $expense->delete();

        return back()->with('status', 'Dépense supprimée.');
    }
}
