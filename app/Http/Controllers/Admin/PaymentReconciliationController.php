<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PaymentReconciliationService;
use Illuminate\View\View;

class PaymentReconciliationController extends Controller
{
    public function index(PaymentReconciliationService $service): View
    {
        return view('admin.finances.reconciliation', $service->anomalies());
    }
}
