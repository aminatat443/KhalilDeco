<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CampaignAutomationController extends Controller
{
    public function edit(): View
    {
        $this->authorize('viewAny', Campaign::class);

        return view('admin.campaigns.automations', ['settings' => Setting::current()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validate([
            'abandoned_cart_enabled' => ['nullable', 'boolean'],
            'abandoned_cart_delay_days' => ['required', 'integer', 'min:1', 'max:30'],
            'low_stock_favorite_enabled' => ['nullable', 'boolean'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100'],
            'promotion_favorite_enabled' => ['nullable', 'boolean'],
            'newsletter_enabled' => ['nullable', 'boolean'],
        ]);

        $data['abandoned_cart_enabled'] = $request->boolean('abandoned_cart_enabled');
        $data['low_stock_favorite_enabled'] = $request->boolean('low_stock_favorite_enabled');
        $data['promotion_favorite_enabled'] = $request->boolean('promotion_favorite_enabled');
        $data['newsletter_enabled'] = $request->boolean('newsletter_enabled');

        Setting::current()->update($data);

        return back()->with('status', 'Automatisations mises à jour.');
    }
}
