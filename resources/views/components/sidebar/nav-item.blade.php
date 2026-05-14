@php
    $routeName = $item['route'];
    $activePattern = $item['activePattern'] ?? $routeName;
    $isActive = request()->routeIs($activePattern);
@endphp

<a
    href="{{ route($routeName) }}"
    @click="sidebarOpen = false"
    @class([
        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors',
        'bg-green-50 font-semibold text-green-700' => $isActive,
        'text-gray-500 hover:bg-green-50 hover:text-green-800' => ! $isActive,
    ])
>
    <img src="{{ asset($item['icon']) }}" alt="" class="h-5 w-5 shrink-0">
    <span class="truncate">{{ $item['label'] }}</span>

    @if (! empty($item['badge']))
        <span class="ml-auto inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-100 px-1.5 text-[11px] font-semibold text-red-700">
            {{ $item['badge'] }}
        </span>
    @endif
</a>
