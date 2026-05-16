@props(['type' => null])

@php
    $labels = [
        'settle_plate'  => 'Settle Plate',
        'air_sampler'   => 'Air Sampler',
        'contact_plate' => 'Contact Plate',
        'swab'          => 'Swab',
    ];

    $label = $labels[$type] ?? ucwords(str_replace('_', ' ', $type ?? ''));
@endphp

<span class="text-gray-700">{{ $label }}</span>
