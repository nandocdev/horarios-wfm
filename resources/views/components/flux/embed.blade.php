@props([
    'src' => null,
    'type' => 'application/pdf',
    'width' => '100%',
    'height' => '500px',
    'title' => 'Documento PDF',
])

@if($src)
    <div {{ $attributes->except(['src', 'type', 'width', 'height', 'title']) }}>
        <embed
            src="{{ $src }}"
            type="{{ $type }}"
            width="{{ $width }}"
            height="{{ $height }}"
            title="{{ $title }}"
        />

        <p class="mt-2 text-xs text-zinc-500">
            Si tu navegador no puede mostrar el archivo, puedes
            <a href="{{ $src }}" target="_blank" rel="noopener noreferrer" class="underline">abrirlo en una pestaña nueva</a>.
        </p>
    </div>
@else
    <p class="text-xs text-red-600">No se proporcionó una URL para el documento.</p>
@endif
