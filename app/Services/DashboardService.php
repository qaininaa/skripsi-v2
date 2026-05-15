<?php

namespace App\Services;

/**
 * Provides helper methods for resolving dashboard view and display data based on user role.
 */
class DashboardService
{
    /**
     * Resolve the Blade view name for the given user role.
     *
     * Returns null if the role does not have a dedicated dashboard view.
     *
     * @param  string|null  $role  The user's role slug (e.g. 'super', 'admin', 'analyst').
     * @return string|null         The Blade view name, or null if unrecognized.
     */
    public function resolveViewByRole(?string $role): ?string
    {
        return match ($role) {
            'super' => 'dashboard.super-admin',
            'admin' => 'dashboard.admin-qc',
            'analyst' => 'dashboard.analyst',
            'supervisor' => 'dashboard.supervisor',
            'manager' => 'dashboard.manager',
            default => null,
        };
    }

    /**
     * Resolve the display name for the given user object.
     *
     * Falls back to 'Pengguna' if the user is null or has no name.
     *
     * @param  object|null  $user  Any object with a nullable `name` property.
     * @return string              The user's display name.
     */
    public function resolveUserName(?object $user): string
    {
        return $user?->name ?? 'Pengguna';
    }
}
