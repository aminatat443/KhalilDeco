<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    /**
     * Inscription depuis le formulaire du footer — ouvert aux visiteurs non connectés,
     * rattaché au compte existant si l'email correspond à un client inscrit.
     */
    public function subscribe(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = auth()->check() && auth()->user()->email === $data['email'] ? auth()->user() : null;

        NewsletterSubscriber::subscribe($data['email'], $user);

        $message = 'Merci ! Vous êtes inscrit à la newsletter.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    /**
     * Désinscription par lien signé/à jeton — accessible sans connexion (le lien figure dans
     * chaque email de newsletter), section 6 et 12 du cahier des charges.
     */
    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();

        if ($subscriber->isActive()) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return view('newsletter.unsubscribed', ['subscriber' => $subscriber]);
    }
}
