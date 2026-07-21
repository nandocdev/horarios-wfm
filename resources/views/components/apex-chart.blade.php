@props([
    'id' => 'chart-' . uniqid(),
    'options' => '{}',
    'height' => 300,
])

<div wire:ignore>
    <div id="{{ $id }}" style="height: {{ $height }}px;" x-data="{
        chart: null,
        init() {
            this.chart = new ApexCharts(this.$el, {{ $options }});
            this.chart.render();
        },
        destroy() {
            if (this.chart) {
                this.chart.destroy();
            }
        }
    }" x-init="init" x-on:livewire:navigating.window="destroy"></div>
</div>
