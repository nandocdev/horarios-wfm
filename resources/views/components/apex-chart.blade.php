@props([
    'id' => 'chart-' . uniqid(),
    'options' => '{}',
    'legend' => null,
    'height' => '100px',
    'refreshEvent' => null,
    'updateEvent' => null,
])

@php
    $optionsArray = is_string($options)
        ? json_decode($options, true) ?? []
        : $options;

    if ($legend !== null) {
        $optionsArray['legend'] = is_string($legend)
            ? json_decode($legend, true) ?? []
            : $legend;
    }

    $serializedOptions = json_encode($optionsArray, JSON_THROW_ON_ERROR);
@endphp

<div wire:ignore class="h-full">
    <div
        id="{{ $id }}"
        style="height: {{ $height }}"
        x-data="apexChart('{{ base64_encode($serializedOptions) }}')"
        @if ($refreshEvent) x-on:{{ $refreshEvent }}.window="refresh()" @endif
        @if ($updateEvent) x-on:{{ $updateEvent }}.window="update($event.detail)" @endif
        x-init="init"
        x-on:livewire:navigating.window="destroy"
    ></div>
</div>
