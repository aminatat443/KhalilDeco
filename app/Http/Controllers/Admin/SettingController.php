<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * Configuration du site : coordonnées de la boutique, logo et signature/cachet affichés
     * sur la facture PDF.
     */
    public function edit(): View
    {
        $this->authorize('viewAny', Setting::class);

        return view('admin.settings.edit', [
            'settings' => Setting::current(),
            'deliveriesCount' => Delivery::count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Setting::class);

        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'shop_address' => ['nullable', 'string', 'max:255'],
            'shop_phone' => ['nullable', 'string', 'max:30'],
            'shop_email' => ['nullable', 'email', 'max:255'],
            'ninea' => ['nullable', 'string', 'max:50'],
            'rccm' => ['nullable', 'string', 'max:50'],
            'invoice_logo' => ['nullable', 'image', 'max:2048'],
            'invoice_signature' => ['nullable', 'image', 'max:2048'],
        ]);

        $settings = Setting::current();
        $disk = Setting::mediaDisk();

        // Chemin relatif au disque actif (pas l'URL résolue) : la facture PDF récupère les
        // octets via ce chemin (Setting::mediaDataUri()) pour fonctionner aussi bien en local
        // qu'avec Cloudinary, sans dépendre des requêtes distantes de DomPDF.
        foreach (['invoice_logo', 'invoice_signature'] as $field) {
            if ($request->hasFile($field)) {
                if ($settings->$field) {
                    Storage::disk($disk)->delete($settings->$field);
                }
                $data[$field] = $request->file($field)->store('settings', $disk);
            } else {
                unset($data[$field]);
            }
        }

        $settings->update($data);

        return back()->with('status', 'Configuration mise à jour.');
    }
}
