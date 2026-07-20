@props([
    'status',
    'label' => null,
    'pulse' => false,
    'size' => 'sm',
])

@php
    $statuses = [
        'available'  => ['dot' => 'bg-wfm-success', 'text' => 'text-wfm-success', 'label' => 'Disponible',    'icon' => 'check-circle'],
        'busy'       => ['dot' => 'bg-wfm-danger',  'text' => 'text-wfm-danger',  'label' => 'En llamada',   'icon' => 'phone'],
        'break'      => ['dot' => 'bg-wfm-warning', 'text' => 'text-wfm-warning', 'label' => 'Break',        'icon' => 'cup-hot'],
        'training'   => ['dot' => 'bg-wfm-info',    'text' => 'text-wfm-info',    'label' => 'Capacitación', 'icon' => 'academic-cap'],
        'offline'    => ['dot' => 'bg-gray-400',    'text' => 'text-gray-400',    'label' => 'Offline',      'icon' => 'x-circle'],
        'lunch'      => ['dot' => 'bg-wfm-warning', 'text' => 'text-wfm-warning', 'label' => 'Almuerzo',     'icon' => 'cake'],
        'meeting'    => ['dot' => 'bg-wfm-info',    'text' => 'text-wfm-info',    'label' => 'Reunión',      'icon' => 'users'],
        'wrapup'     => ['dot' => 'bg-amber-400',   'text' => 'text-amber-500',   'label' => 'Post-llamada', 'icon' => 'document-text'],
    ];
    $s = $statuses[$status] ?? ['dot' => 'bg-gray-400', 'text' => 'text-gray-400', 'label' => $label ?? $status, 'icon' => 'question-mark-circle'];
    $displayLabel = $label ?? $s['label'];
    $sizeClass = $size === 'xs' ? 'text-[10px]' : 'text-xs';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 {$sizeClass} font-medium {$s['text']}"]) }}
    @if($slot) x-data x-tooltip="{ text: '{{ $displayLabel }}' }" @endif>
    @if($pulse)
        <span class="live-pulse">
            <span class="live-pulse-dot"></span>
            <span class="live-pulse-ring"></span>
        </span>
    @else
        <span class="status-dot {{ $s['dot'] }}"></span>
    @endif
    <span>{{ $displayLabel }}</span>
    {{ $slot ?? '' }}
</span>
