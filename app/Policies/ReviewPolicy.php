<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        // File de modération réservée au back-office (section 39 du cahier des charges)
        return $user->isGestionnaire();
    }

    public function update(User $user, Review $review): bool
    {
        if ($user->isGestionnaire()) {
            return true;
        }

        return $user->id === $review->user_id && $review->created_at->addMinutes(30)->isFuture();
    }

    public function delete(User $user, Review $review): bool
    {
        return $user->isGestionnaire() || $user->id === $review->user_id;
    }

    /**
     * Réponse du vendeur à un avis — action strictement réservée au back-office, contrairement
     * à update() qui autorise aussi le client à modifier son propre avis.
     */
    public function reply(User $user, Review $review): bool
    {
        return $user->isGestionnaire();
    }
}
