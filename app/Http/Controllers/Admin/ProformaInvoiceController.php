<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProformaInvoice;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\URL;

class ProformaInvoiceController extends Controller
{
    /**
     * Création d'un devis pro forma — document libre saisi manuellement par l'équipe,
     * indépendant de toute commande (voir ProformaInvoice).
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'integer', 'min:0'],
        ]);

        $proforma = ProformaInvoice::create([
            'reference' => $this->generateReference(),
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_address' => $data['customer_address'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        foreach ($data['items'] as $item) {
            $proforma->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal' => $item['quantity'] * $item['unit_price'],
            ]);
        }

        $url = URL::signedRoute('proforma.show', ['proforma' => $proforma]);

        if ($request->wantsJson()) {
            return response()->json(['url' => $url, 'reference' => $proforma->reference]);
        }

        return redirect($url);
    }

    /**
     * Aperçu / téléchargement du PDF — accessible à l'équipe connectée, ou via un lien signé
     * (généré à la création, voir store()) pour permettre de le partager à un client externe
     * sans qu'il ait besoin d'un compte (WhatsApp, email…).
     */
    public function show(Request $request, ProformaInvoice $proforma): Response
    {
        abort_unless(
            $request->hasValidSignature()
                || (auth()->check() && auth()->user()->isStaffMember()),
            403
        );

        $proforma->load('items');

        $pdf = Pdf::loadView('pdf.proforma', [
            'proforma' => $proforma,
            'settings' => Setting::current(),
        ])->setPaper('a4');

        return $pdf->stream('proforma-'.$proforma->reference.'.pdf');
    }

    private function generateReference(): string
    {
        return 'PF-'.str_pad((string) (ProformaInvoice::max('id') + 1), 6, '0', STR_PAD_LEFT);
    }
}
