{{--
    Partial: template-info.blade.php
    Read-only summary card for a ReportTemplate (Informasi Dasar + Medium + Inkubator).

    Required variables:
      $reportTemplate — ReportTemplate model with mediumTemplates & incubatorTemplates loaded
--}}
<div class="mb-4 rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
    <h2 class="mb-4 text-sm font-semibold text-gray-800">Informasi Dasar</h2>
    <div class="grid grid-cols-2 gap-x-8 gap-y-3">
        <div>
            <p class="text-xs text-gray-500">Kode SOP</p>
            <p class="mt-0.5 text-sm font-medium text-gray-800">{{ $reportTemplate->sop_code }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Versi SOP</p>
            <p class="mt-0.5 text-sm font-medium text-gray-800">{{ $reportTemplate->sop_version }}</p>
        </div>
        <div>
            <p class="text-xs text-gray-500">Annex</p>
            <p class="mt-0.5 text-sm font-medium text-gray-800">{{ $reportTemplate->annex_number }}</p>
        </div>
        <div class="col-span-2">
            <p class="text-xs text-gray-500">Nama</p>
            <p class="mt-0.5 text-sm font-medium text-gray-800">{{ $reportTemplate->name }}</p>
        </div>
    </div>

    @if ($reportTemplate->mediumTemplates->isNotEmpty())
        <div class="mt-4 border-t border-gray-100 pt-4">
            <p class="mb-2 text-xs text-gray-500">Medium</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($reportTemplate->mediumTemplates as $medium)
                    <span class="rounded-full border border-blue-200 bg-blue-50 px-3 py-0.5 text-xs font-medium text-blue-700">
                        {{ $medium->name }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    @if ($reportTemplate->incubatorTemplates->isNotEmpty())
        <div class="mt-4 border-t border-gray-100 pt-4">
            <p class="mb-2 text-xs text-gray-500">Inkubator</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($reportTemplate->incubatorTemplates as $incubator)
                    <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-0.5 text-xs font-medium text-amber-700">
                        {{ $incubator->label }}°C &mdash; min {{ $incubator->min_day }} hari
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>
