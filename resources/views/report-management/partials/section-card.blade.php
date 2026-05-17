{{--
    Partial: section-card.blade.php
    Renders a single section row with edit form, location list, and "tambah lokasi" form.

    Required variables:
      $reportTemplate     — ReportTemplate model
      $section            — Section model
      $index              — Zero-based index for section number badge
      $availableLocations — Collection of Location models that can be assigned
--}}
<div x-data="{ showEdit: false, showAddLocation: false }">
    <div class="px-6 py-4">

        {{-- Section row --}}
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                    {{ $index + 1 }}
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $section->getMeasurementTypeLabel() }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">
                        {{ $section->measurement_unit }}
                        &middot; {{ $section->max_column }}x {{ $section->column_label ?? 'Kolom' }}
                        &middot; Waktu: {{ $section->getTimeSlotLabel() }}
                        @if ($section->has_machine_setup)
                            &middot; Machine Set-up
                        @endif
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button
                    type="button"
                    @click="showEdit = !showEdit"
                    class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-3 py-1 text-xs font-semibold text-gray-600 transition-colors hover:bg-gray-100 cursor-pointer"
                >
                    Edit
                </button>
                <x-buttons.delete
                    :action="route('report-templates.sections.destroy', [$reportTemplate, $section])"
                    :item-name="$section->getMeasurementTypeLabel()"
                    title="Hapus Section"
                    warning="Semua lokasi di section ini akan dihapus."
                />
            </div>
        </div>

        {{-- Inline edit form --}}
        <div x-show="showEdit" x-cloak class="mt-4">
            @include('report-management.partials.section-form', [
                'action'           => route('report-templates.sections.update', [$reportTemplate, $section]),
                'method'           => 'PUT',
                'section'          => $section,
                'cancelExpression' => 'showEdit = false',
            ])
        </div>

        {{-- Lokasi --}}
        <div class="mt-4 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Lokasi ({{ $section->locations->count() }})
                </p>
                @if ($availableLocations->isNotEmpty())
                    <button
                        type="button"
                        @click="showAddLocation = !showAddLocation"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 cursor-pointer"
                    >
                        + Tambah Lokasi
                    </button>
                @else
                    <span class="text-xs text-gray-400 italic">Semua lokasi sudah ditambahkan</span>
                @endif
            </div>

            {{-- Inline select lokasi --}}
            <div x-show="showAddLocation" x-cloak class="mt-3">
                <form
                    method="POST"
                    action="{{ route('report-templates.sections.locations.assign', [$reportTemplate, $section]) }}"
                    class="flex items-center gap-2"
                >
                    @csrf
                    <select
                        name="location_id"
                        required
                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100"
                    >
                        <option value="">— Pilih Lokasi —</option>
                        @foreach ($availableLocations as $loc)
                            <option value="{{ $loc->id }}">
                                {{ $loc->room->name }} - {{ $loc->loc_number }}
                            </option>
                        @endforeach
                    </select>
                    <button
                        type="submit"
                        class="rounded-lg bg-green-700 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-green-800 cursor-pointer"
                    >
                        Tambah
                    </button>
                    <button
                        type="button"
                        @click="showAddLocation = false"
                        class="rounded-lg border border-gray-200 px-3 py-2 text-xs text-gray-600 hover:bg-gray-100 cursor-pointer"
                    >
                        Batal
                    </button>
                </form>
            </div>

            @if ($section->locations->isEmpty())
                <p class="mt-2 text-xs italic text-gray-400">Belum ada lokasi di section ini.</p>
            @else
                @include('report-management.partials.section-location-table', [
                    'reportTemplate' => $reportTemplate,
                    'section'        => $section,
                ])
            @endif
        </div>

    </div>
</div>
