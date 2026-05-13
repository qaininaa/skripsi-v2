@props([
    'action' => '#',
    'label' => 'Hapus',
    'confirm' => 'Yakin ingin menghapus data ini?',
])

<form method="POST" action="{{ $action }}" onsubmit="return confirm('{{ $confirm }}');">
    @csrf
    @method('DELETE')
    <button
        type="submit"
        class="inline-flex items-center rounded-md border border-red-200 bg-red-50 px-3 py-1 text-xs font-semibold text-red-600 transition-colors hover:bg-red-100"
    >
        {{ $label }}
    </button>
</form>
