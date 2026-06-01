@props([
    'note' => null,
])

@if ($note)
    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
        {{ $note }}
    </div>
@endif
