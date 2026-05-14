<?php

namespace App\Services;

use Domain\User\Models\User;
use Illuminate\Support\Facades\Route;

class SidebarService
{
    public function buildForUser(?User $user): array
    {
        if (!$user) {
            return [
                'roleLabel' => null,
                'sections' => [],
                'user' => null,
            ];
        }

        $role = (string) $user->role;

        return [
            'roleLabel' => $this->resolveRoleLabel($role),
            'sections' => $this->resolveSections($role),
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ];
    }

    protected function resolveRoleLabel(string $role): string
    {
        return match ($role) {
            'super' => 'Super Admin',
            'admin' => 'Admin Quality Control',
            'analyst' => 'Analis Lab. Mikrobiologi',
            'supervisor' => 'Supervisor Mikrobiologi',
            'manager' => 'Manajer',
            default => 'Pengguna',
        };
    }

    protected function resolveSections(string $role): array
    {
        $sections = [
            [
                'label' => 'Dashboard',
                'items' => [
                    $this->item('Dashboard', 'dashboard', 'dashboard', 'icons/sidebar/dashboard.svg'),
                ],
            ],
        ];

        if ($role === 'super') {
            $sections[] = [
                'label' => 'Manajemen',
                'items' => [
                    $this->item('Manajemen Pengguna', 'users.index', 'users.*', 'icons/sidebar/users.svg'),
                    $this->item('Audit Trail', 'audit-logs.index', 'audit-logs.*', 'icons/sidebar/audit.svg'),
                    $this->item('Pengaturan Password', 'settings.index', 'settings.*', 'icons/sidebar/settings.svg'),
                ],
            ];
        }

        if ($role === 'admin') {
            $sections[] = [
                'label' => 'Master Data',
                'items' => [
                    $this->item('Ruangan', 'rooms.index', 'rooms.*', 'icons/sidebar/building.svg'),
                    $this->item('Lokasi', 'master.location.index', 'master.location.*', 'icons/sidebar/location.svg'),
                    $this->item('Manajemen Laporan', 'report-types.index', 'report-types.*', 'icons/sidebar/reports.svg'),
                ],
            ];

            $sections[] = [
                'label' => 'Laporan',
                'items' => [
                    $this->item('Tugas Pelaporan', 'report-assignment.index', 'report-assignment.*', 'icons/sidebar/reports.svg'),
                ],
            ];
        }

        if ($role === 'analyst') {
            $sections[] = [
                'label' => 'Laporan',
                'items' => [
                    $this->item('Laporan', 'laporan.index', 'laporan.*', 'icons/sidebar/reports.svg'),
                ],
            ];
        }

        if ($role === 'supervisor') {
            $sections[] = [
                'label' => 'Laporan',
                'items' => [
                    $this->item('Laporan Masuk', 'supervisor.laporan-masuk', 'supervisor.laporan-masuk', 'icons/sidebar/inbox.svg'),
                    $this->item('Sedang Dikerjakan', 'supervisor.laporan-sedang-dikerjakan', 'supervisor.laporan-sedang-dikerjakan', 'icons/sidebar/reports.svg'),
                ],
            ];
        }

        if ($role === 'manager') {
            $sections[] = [
                'label' => 'Laporan',
                'items' => [
                    $this->item('Laporan Masuk', 'manager.laporan-masuk', 'manager.laporan-masuk', 'icons/sidebar/inbox.svg'),
                    $this->item('Sedang Dikerjakan', 'manager.laporan-sedang-dikerjakan', 'manager.laporan-sedang-dikerjakan', 'icons/sidebar/reports.svg'),
                ],
            ];
        }

        if ($role !== 'super') {
            $sections[] = [
                'label' => 'Arsip',
                'items' => [
                    $this->item('Arsip Laporan', 'arsip-laporan.index', 'arsip-laporan.*', 'icons/sidebar/archive.svg'),
                ],
            ];
        }

        return $this->filterUnavailableRoutes($sections);
    }

    protected function item(string $label, string $routeName, string $activePattern, string $icon): array
    {
        return [
            'label' => $label,
            'route' => $routeName,
            'activePattern' => $activePattern,
            'icon' => $icon,
        ];
    }

    protected function filterUnavailableRoutes(array $sections): array
    {
        $filtered = [];

        foreach ($sections as $section) {
            $items = array_values(array_filter(
                $section['items'],
                static fn (array $item): bool => Route::has($item['route'])
            ));

            if ($items === []) {
                continue;
            }

            $filtered[] = [
                'label' => $section['label'],
                'items' => $items,
            ];
        }

        return $filtered;
    }
}
