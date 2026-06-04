@props([
    'printSection',
])

@php
    $instance = $printSection['instance'];
    $section = $printSection['section'];
    $columns = $printSection['columns'];
    $frequencyLabels = [
        'operational' => 'OPERASIONAL',
        'daily' => 'HARIAN',
        'weekly' => 'MINGGUAN',
        'monthly' => 'BULANAN',
        'semi_annual' => 'SEMESTER',
        'unknown' => 'LAINNYA',
    ];
@endphp

<table class="dt small">
    <thead>
        <tr>
            <th rowspan="3">No.</th>
            <th rowspan="3">Nama Ruangan</th>
            <th rowspan="3">Kelas</th>
            <th rowspan="3">No. Ruangan</th>
            <th rowspan="3">No. Lokasi</th>
            <th colspan="{{ count($columns) * 3 }}">{{ $section->measurement_unit }}</th>
            <th colspan="2" rowspan="2">Alert Limit</th>
            <th colspan="2" rowspan="2">Alert Action</th>
            <th rowspan="3">Kesimpulan</th>
        </tr>
        <tr>
            @foreach ($columns as $column)
                <th colspan="3">
                    {{ $column['label'] }}
                    @foreach ($column['sub_columns'] as $subColumn)
                        <div style="font-weight: 400">
                            {{ $subColumn['label'] ? $subColumn['label'] . ': ' : '' }}
                            {{ $subColumn['sp'] }} | {{ $subColumn['time'] }}
                        </div>
                    @endforeach
                </th>
            @endforeach
        </tr>
        <tr>
            @foreach ($columns as $column)
                <th>B</th>
                <th>F</th>
                <th>T</th>
            @endforeach
            <th>T</th>
            <th>F</th>
            <th>T</th>
            <th>F</th>
        </tr>
    </thead>
    <tbody>
        @php
            $rowNumber = 0;
        @endphp

        @foreach ($printSection['rowsByFrequency'] as $frequency => $rows)
            <tr>
                <td colspan="{{ 5 + (count($columns) * 3) + 5 }}">
                    <strong>FREKUENSI : {{ $frequencyLabels[$frequency] ?? strtoupper((string) $frequency) }}</strong>
                </td>
            </tr>
            @foreach ($rows as $printRow)
                @php
                    $rowNumber++;
                    $location = $printRow['location'];
                    $room = $printRow['room'];
                @endphp
                <tr>
                    <td class="tc">{{ $rowNumber }}</td>
                    <td>{{ $room?->name ?? 'N/A' }}</td>
                    <td class="tc">{{ $room?->class ?? 'N/A' }}</td>
                    <td class="tc">{{ $room?->room_number ?? 'N/A' }}</td>
                    <td class="tc">{{ $location?->loc_number ?? 'N/A' }}</td>
                    @foreach ($printRow['cells'] as $cell)
                        <td class="tc">{{ $cell['bacteri'] }}</td>
                        <td class="tc">{{ $cell['fungi'] }}</td>
                        <td class="tc">{{ $cell['total'] }}</td>
                    @endforeach
                    <td class="tc">{{ $location?->alert_limit_total ?? 'N/A' }}</td>
                    <td class="tc">{{ $location?->alert_limit_fungi ?? 'N/A' }}</td>
                    <td class="tc">{{ $location?->alert_action_total ?? 'N/A' }}</td>
                    <td class="tc">{{ $location?->alert_action_fungi ?? 'N/A' }}</td>
                    <td class="tc fw">{{ $printRow['conclusion'] ?? 'MS / TMS*' }}</td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

@if ($instance->note)
    <p class="small" style="margin-top: 6px"><strong>CATATAN :</strong> {{ $instance->note }}</p>
@else
    <div class="small" style="margin-top: 10px">
        <span class="fw">CATATAN :</span>
        <div style="border-bottom: 1px dotted #000; height: 14px; margin: 4px 0"></div>
        <div style="border-bottom: 1px dotted #000; height: 14px; margin: 4px 0"></div>
        <div style="border-bottom: 1px dotted #000; height: 14px; margin: 4px 0"></div>
    </div>
@endif

<div class="small" style="margin-top: 8px">
    <span class="fw">KESIMPULAN</span>
    &ensp;: &ensp;
    @if ($instance->final_conclusion === 'MS')
        <span class="fw">MEMENUHI SPESIFIKASI</span>
    @elseif ($instance->final_conclusion === 'TMS')
        <span class="fw" style="color: #dc2626">TIDAK MEMENUHI SPESIFIKASI</span>
    @else
        <span>N/A</span>
    @endif
</div>

<x-report-print.signatures :signatures="$printSection['signatures']" />
