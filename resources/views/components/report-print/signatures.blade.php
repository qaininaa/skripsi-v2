@props([
    'signatures',
])

@php
    $slots = [
        'monitoring' => ['label' => 'Dimonitoring oleh:', 'subtitle' => '(Analis Lab. Mikrobiologi)'],
        'reading' => ['label' => 'Dibaca oleh:', 'subtitle' => '(Analis Lab. Mikrobiologi)'],
        'review' => ['label' => 'Direview oleh:', 'subtitle' => '(Supervisor Mikrobiologi)'],
        'approval' => ['label' => 'Disetujui oleh:', 'subtitle' => '(QC Manager)'],
    ];
@endphp

<table class="dt sig-tbl">
    <tr>
        @foreach ($slots as $slot)
            <th>{{ $slot['label'] }}</th>
        @endforeach
    </tr>
    <tr>
        @foreach ($slots as $role => $slot)
            <td class="sig-body">
                @forelse ($signatures[$role] ?? [] as $signature)
                    <span style="display: block; font-size: 15pt; line-height: 1; margin-bottom: 2px">&#10003;</span>
                    <strong>{{ $signature['name'] }}</strong><br>
                    {{ $signature['date'] ?? '-' }}<br>
                    {{ $signature['time'] ?? '-' }}
                    @if (! $loop->last)
                        <br><br>
                    @endif
                @empty
                    &nbsp;
                @endforelse
            </td>
        @endforeach
    </tr>
    <tr>
        @foreach ($slots as $slot)
            <td>{{ $slot['subtitle'] }}</td>
        @endforeach
    </tr>
</table>
