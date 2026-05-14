@extends('layouts.app')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')

@section('content')
    @php
        $roleColors = [
            'super' => 'bg-green-100 text-green-700',
            'admin' => 'bg-emerald-100 text-emerald-700',
            'analyst' => 'bg-slate-100 text-slate-700',
            'supervisor' => 'bg-amber-100 text-amber-700',
            'manager' => 'bg-blue-100 text-blue-700',
        ];
    @endphp

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Daftar Pengguna</h2>
            <p class="mt-1 text-sm text-gray-500">Kelola akun pengguna aplikasi.</p>
        </div>
        <a
            href="{{ route('users.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-800"
        >
            <span>+</span>
            <span>Tambah Pengguna</span>
        </a>
    </div>

    <form method="GET" action="{{ route('users.index') }}" class="mb-4 rounded-xl bg-white p-3 shadow-sm ring-1 ring-gray-100">
        <div class="grid gap-3 lg:grid-cols-[1fr_180px_auto_auto]">
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Cari nama atau username..."
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >

            <select
                name="role"
                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
            >
                <option value="">Semua Role</option>
                @foreach (['super', 'admin', 'analyst', 'supervisor', 'manager'] as $role)
                    <option value="{{ $role }}" @selected(request('role') === $role)>{{ ucfirst($role) }}</option>
                @endforeach
            </select>

            <button
                type="submit"
                class="rounded-lg bg-green-700 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-green-800 cursor-pointer"
            >
                Filter
            </button>

            <a
                href="{{ route('users.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-600 transition-colors hover:bg-gray-100"
            >
                Reset
            </a>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Bergabung</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($users as $user)
                        <tr class="hover:bg-gray-50/60">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-xs font-bold text-gray-600">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    <span class="text-sm font-semibold text-gray-800">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $user->username }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                {{ optional($user->created_at)->format('d M Y') }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    <x-buttons.edit :href="route('users.edit', $user)" />
                                    <x-buttons.delete
                                        :action="route('users.destroy', $user)"
                                        :item-name="$user->name"
                                        title="Hapus Pengguna"
                                        warning="Pengguna yang dihapus tidak dapat dikembalikan."
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">
                                Data pengguna tidak ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-gray-100 px-4 py-3">
            {{ $users->links() }}
        </div>
    </div>
@endsection
