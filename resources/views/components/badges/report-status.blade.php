@props(['status'])

@php
    $map = [
        'pending'     => ['label' => 'Pending',      'class' => 'bg-yellow-100 text-yellow-700'],
        'in_progress' => ['label' => 'Dikerjakan',   'class' => 'bg-blue-100 text-blue-700'],
        'completed'   => ['label' => 'Selesai',      'class' => 'bg-green-100 text-green-700'],
        'archived'    => ['label' => 'Diarsipkan',   'class' => 'bg-gray-100 text-gray-600'],
    ];

    $item = $map[$status] ?? ['label' => $status, 'class' => 'bg-gray-100 text-gray-600'];
@endphp

<span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $item['class'] }}">
    {{ $item['label'] }}
</span>
