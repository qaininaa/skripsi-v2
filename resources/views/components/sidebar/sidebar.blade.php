@php
    $user = $sidebarData['user'] ?? null;
    $sections = $sidebarData['sections'] ?? [];
    $roleLabel = $sidebarData['roleLabel'] ?? null;
    $initial = $user !== null ? strtoupper(substr($user['name'], 0, 1)) : 'U';
@endphp

<div class="flex h-full flex-col border-r border-gray-100 bg-white text-gray-800">
    <div class="flex h-16 flex-shrink-0 items-center border-b border-gray-100 px-5">
        <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-green-600">
                <img src="{{ asset('icons/sidebar/brand.svg') }}" alt="Brand" class="h-5 w-5">
            </div>
            <span class="text-base font-bold tracking-tight text-gray-900">Quality Control Panel</span>
        </div>
    </div>

    @if ($roleLabel !== null)
        <div class="flex-shrink-0 border-b border-gray-100 px-5 py-3">
            <span class="inline-flex items-center rounded-full bg-green-50 px-2.5 py-1 text-xs font-semibold text-green-700">
                {{ $roleLabel }}
            </span>
        </div>
    @endif

    <nav class="flex-1 space-y-0.5 overflow-y-auto px-3 py-4">
        @foreach ($sections as $section)
            <div class="px-3 pb-1 pt-2">
                <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">{{ $section['label'] }}</p>
            </div>

            @foreach ($section['items'] as $item)
                @include('components.sidebar.nav-item', ['item' => $item])
            @endforeach
        @endforeach
    </nav>

    @if ($user !== null)
        <div class="flex-shrink-0 border-t border-gray-100 p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-bold text-green-700">
                    {{ $initial }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gray-800">{{ $user['name'] }}</p>
                    <p class="truncate text-xs text-gray-400">{{ $user['email'] }}</p>
                </div>
            </div>
        </div>
    @endif
</div>
