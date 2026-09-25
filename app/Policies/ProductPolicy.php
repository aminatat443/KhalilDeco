<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(?User $user): bool
    {
        return true; // catalogue public
    }

    public function view(?User $user, Product $product): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isGestionnaire() || $user->hasPermission('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        // Couvre aussi la désactivation et la gestion du stock/variantes/images (docs/SPEC.md §2.5)
        // — approximation volontairement large : le formulaire produit n'expose pas de permissions
        // fines par champ (prix / promo / images / variantes séparément), donc n'importe laquelle
        // de ces permissions RBAC donne accès au formulaire complet.
        return $user->isGestionnaire()
            || $user->hasPermission('products.update')
            || $user->hasPermission('products.edit_price')
            || $user->hasPermission('products.edit_promotions')
            || $user->hasPermission('products.manage_images')
            || $user->hasPermission('products.manage_variants');
    }

    public function delete(User $user, Product $product): bool
    {
        // Suppression définitive réservée à l'Administrateur/Super Administrateur
        return $user->isAdmin();
    }
}
