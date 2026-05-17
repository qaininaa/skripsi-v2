<?php

namespace App\Services;

use Domain\User\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Builds the sidebar navigation structure for the authenticated user.
 *
 * Returns role-aware sections and items, filtered to only include
 * routes that are currently registered in the application.
 */
class SidebarService
{
    /**
     * Build the complete sidebar data array for the given user.
     *
     * Returns an array with keys: roleLabel, sections, user.
     * Returns an empty structure if no user is provided.
     *
     * @param  User|null  $user  The authenticated user, or null for unauthenticated context.
     * @return array{
     *     roleLabel: string|null,
     *     sections: array<int, array{label: string, items: array<int, array{label: string, route: string, activePattern: string, icon: string}>}>,
     *     user: array{name: string, email: string}|null
     * }
     */
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

    /**
     * Resolve a human-readable label for the given role slug.
     *
     * @param  string  $role  The role slug.
     * @return string         The localized role label.
     */
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

    /**
     * Build the ordered list of sidebar sections for the given role.
     *
     * Each section has a label and an array of navigation items.
     * Sections containing no registered routes are automatically excluded.
     *
     * @param  string  $role  The role slug.
     * @return array<int, array{label: string, items: array<int, array{label: string, route: string, activePattern: string, icon: string}>}>
     */
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
                    $this->item('Lokasi', 'location.index', 'location.*', 'icons/sidebar/location.svg'),
                    $this->item('Manajemen Laporan', 'report-templates.index', 'report-templates.*', 'icons/sidebar/reports.svg'),
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
                    $this->item('Tugas Pelaporan', 'analyst.reports.index', 'analyst.reports.*', 'icons/sidebar/reports.svg'),
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

    /**
     * Create a single navigation item array.
     *
     * @param  string  $label          The display label.
     * @param  string  $routeName      The named route to link to.
     * @param  string  $activePattern  The route pattern used to determine active state (supports wildcards).
     * @param  string  $icon           The asset path to the sidebar icon SVG.
     * @return array{label: string, route: string, activePattern: string, icon: string}
     */
    protected function item(string $label, string $routeName, string $activePattern, string $icon): array
    {
        return [
            'label' => $label,
            'route' => $routeName,
            'activePattern' => $activePattern,
            'icon' => $icon,
        ];
    }

    /**
     * Remove sections and items whose named routes are not registered.
     *
     * Sections that become empty after filtering are also removed.
     *
     * @param  array<int, array{label: string, items: array<int, array{label: string, route: string, activePattern: string, icon: string}>}>  $sections
     * @return array<int, array{label: string, items: array<int, array{label: string, route: string, activePattern: string, icon: string}>}>
     */
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
