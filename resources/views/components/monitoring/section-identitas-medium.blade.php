{{--
    Section 3: Identitas Medium
    Props:
      $mediumEntries — Collection<MediumEntry> sorted non-swab first
      $readonly      — bool
--}}
@props(['mediumEntries', 'readonly' => true])

@php
    $inputClass = $readonly
        ? 'w-full rounded-lg border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-400 cursor-not-allowed'
        : 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100';
@endphp

<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h2 class="mb-4 text-base font-bold text-gray-800">3. Identitas Medium</h2>

    @if ($mediumEntries->isEmpty())
        <p class="text-sm italic text-gray-400">Belum ada data medium. Laporan perlu dimulai terlebih dahulu.</p>
    @else
        <div class="grid gap-6 lg:grid-cols-3">
            @foreach ($mediumEntries as $medium)
                <div class="space-y-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-blue-500">
                        {{ strtoupper($medium->name ?? '—') }}
                    </h3>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">Nomor Batch Medium</label>
                        <input
                            type="text"
                            name="mediums[{{ $medium->id }}][batch_number]"
                            value="{{ old('mediums.' . $medium->id . '.batch_number', $medium->batch_number) }}"
                            placeholder="{{ $readonly ? '—' : '' }}"
                            @disabled($readonly)
                            class="{{ $inputClass }}"
                        >
                    </div>

                    @unless ($medium->is_swab)
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Nomor GPT Medium</label>
                            <input
                                type="text"
                                name="mediums[{{ $medium->id }}][gpt_number]"
                                value="{{ old('mediums.' . $medium->id . '.gpt_number', $medium->gpt_number) }}"
                                placeholder="{{ $readonly ? '—' : '' }}"
                                @disabled($readonly)
                                class="{{ $inputClass }}"
                            >
                        </div>
                    @endunless

                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">
                            {{ $medium->is_swab ? 'Tanggal ED Swab Kit' : 'Tanggal ED Medium' }}
                        </label>
                        <input
                            type="date"
                            name="mediums[{{ $medium->id }}][expiration_date]"
                            value="{{ old('mediums.' . $medium->id . '.expiration_date', optional($medium->expiration_date)->format('Y-m-d')) }}"
                            @disabled($readonly)
                            class="{{ $inputClass }}"
                        >
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
