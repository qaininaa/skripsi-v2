@props([
    'roomMonitoring',
    'instrumentEntries',
    'mediumEntries',
])

<div>
    <table class="dt dt-auto" style="margin-bottom: 8px">
        <tr><td colspan="2" class="sec-hdr">1. Pemantauan Ruang</td></tr>
        <tr><td style="width: 45%">Tanggal Pemantauan Ruang</td><td>{{ $roomMonitoring['date'] }}</td></tr>
        <tr><td>Nama Analis</td><td>{{ $roomMonitoring['monitoringAnalysts'] }}</td></tr>
        <tr><td>Dibaca Oleh</td><td>{{ $roomMonitoring['readingAnalysts'] }}</td></tr>
        <tr><td>Nama Produk</td><td>{{ $roomMonitoring['productName'] }}</td></tr>
        <tr><td>Nomor Batch Produk</td><td>{{ $roomMonitoring['batchNumber'] }}</td></tr>
    </table>
</div>

<div>
    <table class="dt dt-auto" style="margin-bottom: 8px">
        <tr><td colspan="2" class="sec-hdr">2. Identitas Instrumen</td></tr>
        @forelse ($instrumentEntries as $instrument)
            <tr><td style="width: 45%">Nama Alat</td><td class="fw">{{ $instrument->tool_name ?? 'N/A' }}</td></tr>
            <tr><td>No. ID {{ $instrument->tool_name ?? 'Instrumen' }}</td><td>{{ $instrument->no_id ?? '' }}</td></tr>
            <tr><td>Tanggal Kalibrasi {{ $instrument->tool_name ?? 'Instrumen' }}</td><td>{{ optional($instrument->calibration_date)->format('d/m/Y') ?? '' }}</td></tr>
            <tr><td>Tanggal Due Date Kalibrasi {{ $instrument->tool_name ?? 'Instrumen' }}</td><td>{{ optional($instrument->due_date)->format('d/m/Y') ?? '' }}</td></tr>
            @if (! $loop->last)
                <tr><td colspan="2" style="border-left: 1px solid #000; border-right: 1px solid #000; padding: 5px"></td></tr>
            @endif
        @empty
            <tr><td style="width: 45%">Nama Alat</td><td></td></tr>
        @endforelse
    </table>
</div>

<div>
    <table class="dt dt-auto" style="margin-bottom: 8px">
        <tr><td colspan="2" class="sec-hdr">3. Identitas Medium</td></tr>
        @forelse ($mediumEntries as $medium)
            @php
                $mediumName = $medium->name ?? $medium->template?->name ?? 'Medium';
                $isSwab = str_contains(strtolower($mediumName), 'swab');
            @endphp
            <tr><td style="width: 45%">Nomor Batch {{ $mediumName }}</td><td>{{ $medium->batch_number ?? '' }}</td></tr>
            @if (! $isSwab)
                <tr><td>Nomor GPT {{ $mediumName }}</td><td>{{ $medium->gpt_number ?? '' }}</td></tr>
            @endif
            <tr><td>Tanggal ED {{ $mediumName }}</td><td>{{ optional($medium->expiration_date)->format('d/m/Y') ?? '' }}</td></tr>
            @if (! $loop->last)
                <tr><td colspan="2" style="border-left: 1px solid #000; border-right: 1px solid #000; padding: 5px"></td></tr>
            @endif
        @empty
            <tr><td style="width: 45%">Nomor Batch Medium</td><td></td></tr>
        @endforelse
    </table>
</div>
