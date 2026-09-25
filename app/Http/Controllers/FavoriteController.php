<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Ajoute/retire un favori pour le client connecté. Les invités restent en localStorage
     * uniquement (voir $store.favorites côté client) — sans compte, on n'a pas d'email à notifier.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'is_favorite' => ['required', 'boolean'],
        ]);

        if ($data['is_favorite']) {
            Favorite::firstOrCreate(['user_id' => $request->user()->id, 'product_id' => $data['product_id']]);
        } else {
            Favorite::where('user_id', $request->user()->id)->where('product_id', $data['product_id'])->delete();
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Fusionne les favoris ajoutés localement avant connexion avec ceux déjà enregistrés côté
     * serveur — appelé une seule fois au chargement de la page pour un client qui vient de se
     * connecter et dont le localStorage contient des favoris inconnus du serveur.
     */
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_ids' => ['array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        foreach ($data['product_ids'] ?? [] as $productId) {
            Favorite::firstOrCreate(['user_id' => $request->user()->id, 'product_id' => $productId]);
        }

        return response()->json(['items' => Favorite::summaryFor($request->user()->id)]);
    }
}
