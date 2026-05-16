@props([
    'class' => 'mt-4',
])

@if ($errors->any())
    <div {{ $attributes->merge(['class' => $class . ' rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700']) }}>
        <ul class="list-disc space-y-1 pl-4">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
