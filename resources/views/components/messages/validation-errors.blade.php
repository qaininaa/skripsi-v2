{{--
    Generic validation error summary.

    Props:
      $class  — extra classes for the container
      $except — array of field keys whose messages should be hidden from this
                summary (used for fields that render their own inline error,
                e.g. modal username/password).
--}}
@props([
    'class'  => 'mt-4',
    'except' => [],
])

@php
    $skip = (array) $except;

    // Flatten only messages for fields not in $skip.
    $messages = collect($errors->getMessages())
        ->reject(fn ($_, $field) => in_array($field, $skip, true))
        ->flatMap(fn ($items) => $items)
        ->values();
@endphp

@if ($messages->isNotEmpty())
    <div {{ $attributes->merge(['class' => $class . ' rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700']) }}>
        <ul class="list-disc space-y-1 pl-4">
            @foreach ($messages as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
