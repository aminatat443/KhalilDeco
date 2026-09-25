<?php

namespace App\Http\Controllers;

use App\Models\CampaignSend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Suivi réel des ouvertures/clics d'emails de campagne (section 14 du cahier des charges
 * multi-rôles) — URL signées pour éviter qu'un identifiant de CampaignSend deviné ne pollue les
 * statistiques d'un autre envoi. Aucune tentative de suivi pour les emails transactionnels
 * (confirmation de commande...), seulement les campagnes marketing.
 */
class EmailTrackingController extends Controller
{
    private const PIXEL = "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";

    public function pixel(Request $request, CampaignSend $send): Response
    {
        if ($request->hasValidSignature()) {
            $send->markOpened();
        }

        return response(self::PIXEL, 200, ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store, no-cache, must-revalidate']);
    }

    public function click(Request $request, CampaignSend $send): RedirectResponse
    {
        $url = $request->query('url');

        if ($request->hasValidSignature() && $url && str_starts_with($url, url('/'))) {
            $send->recordClick();
        }

        return redirect($url && str_starts_with($url, url('/')) ? $url : route('home'));
    }
}
