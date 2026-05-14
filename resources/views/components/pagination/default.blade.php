@if ($paginator->hasPages())
    @php
        $current  = $paginator->currentPage();
        $last     = $paginator->lastPage();
        $baseUrl  = strtok($paginator->url(1), '?');
        $query    = request()->except('page');

        // Build page URL helper
        $pageUrl = fn(int $p) => $baseUrl . '?' . http_build_query(array_merge($query, ['page' => $p]));

        // Decide which page numbers to show
        if ($last < 7) {
            // Show all pages
            $pages = range(1, $last);
            $showStartEllipsis = false;
            $showEndEllipsis   = false;
        } else {
            // Window of 3 around current: current-1, current, current+1
            $windowStart = max(2, $current - 1);
            $windowEnd   = min($last - 1, $current + 1);

            $showStartEllipsis = $windowStart > 2;
            $showEndEllipsis   = $windowEnd < $last - 1;

            // Middle pages (excluding first and last which are always shown)
            $pages = range($windowStart, $windowEnd);
        }
    @endphp

    <nav
        role="navigation"
        aria-label="Pagination"
        class="flex flex-wrap items-center justify-between gap-3"
    >
        {{-- Info --}}
        <p class="text-xs text-gray-500 shrink-0">
            Menampilkan
            <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}</span>
            &ndash;
            <span class="font-semibold text-gray-700">{{ $paginator->lastItem() }}</span>
            dari
            <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span>
            data
        </p>

        {{-- Buttons --}}
        <ul class="flex flex-wrap items-center gap-1">

            {{-- Prev --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-300 cursor-not-allowed select-none">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </span>
                @else
                    <a href="{{ $pageUrl($current - 1) }}" rel="prev" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-colors hover:border-green-300 hover:bg-green-50 hover:text-green-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                @endif
            </li>

            @if ($last < 7)
                {{-- Show all pages --}}
                @foreach ($pages as $page)
                    <li>
                        @if ($page === $current)
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-green-700 text-xs font-semibold text-white shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $pageUrl($page) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-600 transition-colors hover:border-green-300 hover:bg-green-50 hover:text-green-700">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach
            @else
                {{-- Always show page 1 --}}
                <li>
                    @if ($current === 1)
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-green-700 text-xs font-semibold text-white shadow-sm">1</span>
                    @else
                        <a href="{{ $pageUrl(1) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-600 transition-colors hover:border-green-300 hover:bg-green-50 hover:text-green-700">1</a>
                    @endif
                </li>

                {{-- Start ellipsis (jump input) --}}
                @if ($showStartEllipsis)
                    <li
                        x-data="{ open: false, val: '' }"
                        class="relative"
                    >
                        <button
                            @click="open = !open"
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs text-gray-400 transition-colors hover:border-green-300 hover:bg-green-50 hover:text-green-700"
                            title="Lompat ke halaman"
                        >
                            &hellip;
                        </button>
                        <div
                            x-show="open"
                            x-transition
                            @click.outside="open = false"
                            class="absolute bottom-10 left-1/2 z-50 -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-2 shadow-lg"
                        >
                            <p class="mb-1.5 whitespace-nowrap text-xs text-gray-500">Lompat ke halaman</p>
                            <form
                                method="GET"
                                action="{{ $baseUrl }}"
                                @submit.prevent="
                                    const p = parseInt(val);
                                    if (p >= 1 && p <= {{ $last }}) {
                                        window.location = '{{ $baseUrl }}?' + new URLSearchParams({...{{ json_encode($query) }}, page: p}).toString();
                                    }
                                "
                                class="flex gap-1.5"
                            >
                                <input
                                    x-model="val"
                                    type="number"
                                    min="1"
                                    max="{{ $last }}"
                                    placeholder="1–{{ $last }}"
                                    class="w-20 rounded-md border border-gray-300 px-2 py-1 text-xs focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-200"
                                >
                                <button type="submit" class="rounded-md bg-green-700 px-2 py-1 text-xs font-medium text-white hover:bg-green-800">Go</button>
                            </form>
                        </div>
                    </li>
                @endif

                {{-- Window pages --}}
                @foreach ($pages as $page)
                    <li>
                        @if ($page === $current)
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-green-700 text-xs font-semibold text-white shadow-sm">{{ $page }}</span>
                        @else
                            <a href="{{ $pageUrl($page) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-600 transition-colors hover:border-green-300 hover:bg-green-50 hover:text-green-700">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach

                {{-- End ellipsis (jump input) --}}
                @if ($showEndEllipsis)
                    <li
                        x-data="{ open: false, val: '' }"
                        class="relative"
                    >
                        <button
                            @click="open = !open"
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs text-gray-400 transition-colors hover:border-green-300 hover:bg-green-50 hover:text-green-700"
                            title="Lompat ke halaman"
                        >
                            &hellip;
                        </button>
                        <div
                            x-show="open"
                            x-transition
                            @click.outside="open = false"
                            class="absolute bottom-10 left-1/2 z-50 -translate-x-1/2 rounded-lg border border-gray-200 bg-white p-2 shadow-lg"
                        >
                            <p class="mb-1.5 whitespace-nowrap text-xs text-gray-500">Lompat ke halaman</p>
                            <form
                                method="GET"
                                action="{{ $baseUrl }}"
                                @submit.prevent="
                                    const p = parseInt(val);
                                    if (p >= 1 && p <= {{ $last }}) {
                                        window.location = '{{ $baseUrl }}?' + new URLSearchParams({...{{ json_encode($query) }}, page: p}).toString();
                                    }
                                "
                                class="flex gap-1.5"
                            >
                                <input
                                    x-model="val"
                                    type="number"
                                    min="1"
                                    max="{{ $last }}"
                                    placeholder="1–{{ $last }}"
                                    class="w-20 rounded-md border border-gray-300 px-2 py-1 text-xs focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-200"
                                >
                                <button type="submit" class="rounded-md bg-green-700 px-2 py-1 text-xs font-medium text-white hover:bg-green-800">Go</button>
                            </form>
                        </div>
                    </li>
                @endif

                {{-- Always show last page --}}
                <li>
                    @if ($current === $last)
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-green-700 text-xs font-semibold text-white shadow-sm">{{ $last }}</span>
                    @else
                        <a href="{{ $pageUrl($last) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-xs font-medium text-gray-600 transition-colors hover:border-green-300 hover:bg-green-50 hover:text-green-700">{{ $last }}</a>
                    @endif
                </li>
            @endif

            {{-- Next --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $pageUrl($current + 1) }}" rel="next" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 transition-colors hover:border-green-300 hover:bg-green-50 hover:text-green-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </a>
                @else
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-gray-50 text-gray-300 cursor-not-allowed select-none">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    </span>
                @endif
            </li>

        </ul>
    </nav>
@endif
