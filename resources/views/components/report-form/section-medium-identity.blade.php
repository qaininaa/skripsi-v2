{{--
    Section 3: Identitas Medium

    Props:
      $mediumEntries — Collection<MediumEntry> sorted non-swab first
      $readonly      — bool
      $lockMap       — [table][row_id][field_name] => FieldLock
--}}
@props([
    'mediumEntries',
    'readonly' => true,
    'lockMap'  => [],
])

@php
    $currentUserId = (string) (auth()->id() ?? '');

    $isLockedByOther = function (string $rowId, string $field) use ($lockMap, $currentUserId) {
        $lock = $lockMap['medium_entries'][$rowId][$field] ?? null;
        return $lock !== null && (string) $lock->filled_by !== $currentUserId;
    };
    $lockOwner = function (string $rowId, string $field) use ($lockMap) {
        $lock = $lockMap['medium_entries'][$rowId][$field] ?? null;
        return $lock?->filler?->name;
    };

    $editableClass = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100';
    $readonlyClass = 'w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-400 cursor-not-allowed';
    $lockedClass   = 'flex w-full items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700';
@endphp

<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h2 class="mb-4 text-base font-bold text-gray-800">3. Identitas Medium</h2>

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($mediumEntries as $medium)
            @php
                // Preview rows (template-derived) carry an id like "preview-..." and
                // therefore have no lock entries. Treat them as fully editable.
                $rowId      = (string) ($medium->id ?? '');
                $isPersisted = ! str_starts_with($rowId, 'preview-');

                $batchLocked = $isPersisted && $isLockedByOther($rowId, 'batch_number');
                $gptLocked   = $isPersisted && $isLockedByOther($rowId, 'gpt_number');
                $expLocked   = $isPersisted && $isLockedByOther($rowId, 'expiration_date');

                $batchLockedBy = $isPersisted ? $lockOwner($rowId, 'batch_number') : null;
                $gptLockedBy   = $isPersisted ? $lockOwner($rowId, 'gpt_number') : null;
                $expLockedBy   = $isPersisted ? $lockOwner($rowId, 'expiration_date') : null;
            @endphp

            <div class="space-y-3">
                <h3 class="text-sm font-semibold uppercase tracking-wide text-blue-500">
                    {{ strtoupper($medium->name ?? '—') }}
                </h3>

                {{-- Nomor Batch Medium --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Nomor Batch Medium</label>
                    @if (! $readonly && ! $batchLocked)
                        <input
                            type="text"
                            name="mediums[{{ $medium->id }}][batch_number]"
                            value="{{ old('mediums.' . $medium->id . '.batch_number', $medium->batch_number) }}"
                            class="{{ $editableClass }}"
                        >
                    @elseif (! $readonly && $batchLocked)
                        <input
                            type="text"
                            value="{{ $medium->batch_number }}"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                        <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $batchLockedBy }}</p>
                    @else
                        <input
                            type="text"
                            value="{{ $medium->batch_number }}"
                            placeholder="—"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                    @endif
                </div>

                @unless ($medium->is_swab)
                    {{-- Nomor GPT Medium --}}
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">Nomor GPT Medium</label>
                        @if (! $readonly && ! $gptLocked)
                            <input
                                type="text"
                                name="mediums[{{ $medium->id }}][gpt_number]"
                                value="{{ old('mediums.' . $medium->id . '.gpt_number', $medium->gpt_number) }}"
                                class="{{ $editableClass }}"
                            >
                        @elseif (! $readonly && $gptLocked)
                            <input
                                type="text"
                                value="{{ $medium->gpt_number }}"
                                disabled
                                class="{{ $readonlyClass }}"
                            >
                            <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $gptLockedBy }}</p>
                        @else
                            <input
                                type="text"
                                value="{{ $medium->gpt_number }}"
                                placeholder="—"
                                disabled
                                class="{{ $readonlyClass }}"
                            >
                        @endif
                    </div>
                @endunless

                {{-- Tanggal ED --}}
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">
                        {{ $medium->is_swab ? 'Tanggal ED Swab Kit' : 'Tanggal ED Medium' }}
                    </label>
                    @if (! $readonly && ! $expLocked)
                        <input
                            type="date"
                            name="mediums[{{ $medium->id }}][expiration_date]"
                            value="{{ old('mediums.' . $medium->id . '.expiration_date', optional($medium->expiration_date)->format('Y-m-d')) }}"
                            class="{{ $editableClass }}"
                        >
                    @elseif (! $readonly && $expLocked)
                        <input
                            type="date"
                            value="{{ optional($medium->expiration_date)->format('Y-m-d') }}"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                        <p class="mt-1 text-[11px] italic text-gray-400">Telah diisi oleh {{ $expLockedBy }}</p>
                    @else
                        <input
                            type="date"
                            value="{{ optional($medium->expiration_date)->format('Y-m-d') }}"
                            disabled
                            class="{{ $readonlyClass }}"
                        >
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</section>
