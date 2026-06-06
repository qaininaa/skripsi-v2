@props([
    'incubators',
    'hasSwab' => false,
])

@php
    $mediumRows = $hasSwab ? ['monitoring', 'swab'] : ['monitoring'];
    $mediumLabels = [
        'monitoring' => 'Medium Monitoring',
        'swab' => 'Swab',
    ];
@endphp

<div>
    @foreach ($incubators as $incubator)
        @php
            $entries = $incubator->entries->keyBy('medium_type');
            $incubatorName = $incubator->template?->label ?? 'N/A';
        @endphp

        <table class="dt dt-auto dt-compact" style="{{ ! $loop->first ? 'margin-top: 12px' : '' }}">
            @if ($loop->first)
                <tr><td colspan="4" class="sec-hdr">4. Proses Inkubasi Medium Monitoring</td></tr>
            @endif
            <tr><td style="width: 35%">Nama Alat</td><td colspan="3" class="fw">Inkubator Suhu {{ $incubatorName }}</td></tr>
            <tr><td>No. ID Inkubator</td><td colspan="3">{{ $incubator->no_id ?? '' }}</td></tr>
            <tr><td>Tanggal Kalibrasi Inkubator</td><td colspan="3">{{ optional($incubator->calibration_date)->format('d/m/Y') ?? '' }}</td></tr>
            <tr><td>Tanggal Due Date Kalibrasi Inkubator</td><td colspan="3">{{ optional($incubator->due_date_calibration)->format('d/m/Y') ?? '' }}</td></tr>
            @foreach ($mediumRows as $mediumType)
                @php $entry = $entries->get($mediumType); @endphp
                @if ($entry === null)
                    @continue
                @endif
                <tr>
                    <td rowspan="4" style="vertical-align: middle">Tanggal Inkubasi {{ $mediumLabels[$mediumType] ?? $mediumType }} (min {{ $incubator->template?->min_day ?? '-' }} hari)</td>
                    <td rowspan="4" class="tc" style="vertical-align: middle; width: 14%">{{ $mediumLabels[$mediumType] ?? $mediumType }}</td>
                    <td>Tanggal Masuk Inkubator: {{ optional($entry->date_in)->format('d/m/Y') ?? '' }}</td>
                    <td style="width: 14%">Jam: {{ $entry->time_in ?? '' }}</td>
                </tr>
                <tr><td colspan="2">Diinkubasi oleh (paraf, inisial, tanggal): {{ $entry->incubatedBy?->name ?? '' }}{{ $entry->date_in ? ', ' . $entry->date_in->format('d/m/Y') : '' }}</td></tr>
                <tr><td>Tanggal Keluar Inkubator: {{ optional($entry->date_out)->format('d/m/Y') ?? '' }}</td><td>Jam: {{ $entry->time_out ?? '' }}</td></tr>
                <tr><td colspan="2">Dikeluarkan oleh (paraf, inisial, tanggal): {{ $entry->removedBy?->name ?? '' }}{{ $entry->date_out ? ', ' . $entry->date_out->format('d/m/Y') : '' }}</td></tr>
            @endforeach
        </table>
    @endforeach
</div>
