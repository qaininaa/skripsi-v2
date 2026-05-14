@props([
    'title' => 'Dashboard',
    'description' => 'Selamat datang di dashboard.',
    'name' => 'Pengguna',
])

<div class="mb-6 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-700 p-6 text-white shadow-lg">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm font-medium text-emerald-200">{{ $title }}</p>
            <h2 class="mt-1 text-2xl font-bold leading-tight">Selamat Datang, {{ $name }}!</h2>
            <p class="mt-1 text-sm text-emerald-200">{{ $description }}</p>
            <div class="mt-3 flex items-center gap-4 text-xs text-emerald-300">
                <span>{{ now()->locale('id')->isoFormat('dddd, D MMMM Y') }}</span>
            </div>
        </div>
        <div class="hidden shrink-0 items-center justify-center rounded-2xl bg-white/10 p-4 md:flex">
            <svg class="h-10 w-10 text-white opacity-80" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
            </svg>
        </div>
    </div>
</div>
