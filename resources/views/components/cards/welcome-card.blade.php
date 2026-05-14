@props([
    'title' => 'Dashboard',
    'description' => 'Selamat datang di dashboard.',
])

<div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
    <p class="mt-2 text-sm text-gray-600">{{ $description }}</p>
</div>
