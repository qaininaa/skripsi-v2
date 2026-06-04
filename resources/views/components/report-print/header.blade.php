@props([
    'report',
])

@php
    $template = $report->reportTemplate;
@endphp

<div class="pg-hdr">
    <div class="doc-num">
        {{ $template?->sop_code ?? '-' }}<br>
        Ver.{{ $template?->sop_version ?? '-' }}
    </div>
    <div class="doc-title">{{ strtoupper($template?->name ?? 'LAPORAN PEMANTAUAN RUANGAN') }}</div>
    <hr class="doc-title-line">
</div>
