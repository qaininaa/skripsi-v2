<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Provides helper methods for resolving dashboard view and display data based on user role.
 */
class DashboardService
{
    /**
     * Resolve the Blade view name for the given user role.
     *
     * Throws InvalidArgumentException if the role does not have a dedicated dashboard view,
     *
     * @param  string|null  $role  
     * @return string              
     *
     * @throws InvalidArgumentException  If the role is null or unrecognized.
     */
    public function resolveViewByRole(?string $role): string
    {
        return match ($role) {
            'super'      => 'dashboard.super-admin',
            'admin'      => 'dashboard.admin-qc',
            'analyst'    => 'dashboard.analyst',
            'supervisor' => 'dashboard.supervisor',
            'manager'    => 'dashboard.manager',
            default      => throw new InvalidArgumentException(
                "Tidak ada tampilan dashboard yang dikonfigurasi untuk peran: [{$role}]"
            ),
        };
    }

    /**
     * Resolve the display name for the given user object.
     *
     * @param  object|null  $user 
     * @return string              
     */
    public function resolveUserName(?object $user): string
    {
        return $user?->name ?? 'Pengguna';
    }
}
