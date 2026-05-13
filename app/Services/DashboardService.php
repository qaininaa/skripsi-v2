<?php

namespace App\Services;

class DashboardService
{
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
}
