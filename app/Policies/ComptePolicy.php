<?php

namespace App\Policies;

use App\Models\Compte;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ComptePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Admin voit tous les comptes, client voit seulement ses comptes
        return true; // Filtrage fait dans le contrôleur
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Compte $compte): bool
    {
        // Admin peut voir tous les comptes
        if ($user->isAdmin()) {
            return true;
        }

        // Client ne peut voir que ses propres comptes
        return $compte->client_id === $user->client_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Seuls les admins peuvent créer des comptes
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Compte $compte): bool
    {
        // Admin peut modifier tous les comptes
        if ($user->isAdmin()) {
            return true;
        }

        // Client ne peut modifier que ses propres comptes
        return $compte->client_id === $user->client_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Compte $compte): bool
    {
        // Seuls les admins peuvent supprimer des comptes
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can block the account.
     */
    public function bloquer(User $user, Compte $compte): bool
    {
        // Seuls les admins peuvent bloquer des comptes
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Compte $compte): bool
    {
        // Seuls les admins peuvent restaurer des comptes
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Compte $compte): bool
    {
        // Seuls les admins peuvent supprimer définitivement
        return $user->isAdmin();
    }
}
