@props([
    'note' => null,
    'href' => null,
    'actionLabel' => null,
])

@if ($note)
    <div class="mb-6 flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800 sm:flex-row sm:items-center sm:justify-between">
        <span>{{ $note }}</span>
        @if ($href && $actionLabel)
            <a
                href="{{ $href }}"
                class="inline-flex w-fit items-center rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition-colors hover:opacity-80"
            >
                {{ $actionLabel }}
            </a>
        @endif
    </div>
@endif
