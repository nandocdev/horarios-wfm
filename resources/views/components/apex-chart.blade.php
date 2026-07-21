@props([
    'id' => 'chart-' . uniqid(),
    'options' => '{}',
    'height' => 300,
])

<div wire:ignore>
    <div id="{{ $id }}" style="height: {{ $height }}px;"
         x-data="{
            chart: null,
            baseOptions: JSON.parse(atob('{{ base64_encode($options) }}')),
            init() {
                if (this.chart) {
                    this.chart.destroy();
                    this.chart = null;
                }
                let opts = this.buildOptions();
                this.chart = new ApexCharts(this.$el, opts);
                this.chart.render();
            },
            buildOptions() {
                let opts = JSON.parse(JSON.stringify(this.baseOptions));
                if (opts.xaxis?.labels?.formatter && typeof opts.xaxis.labels.formatter === 'string') {
                    opts.xaxis.labels.formatter = (new Function('return ' + opts.xaxis.labels.formatter))();
                }
                if (opts.yaxis?.labels?.formatter && typeof opts.yaxis.labels.formatter === 'string') {
                    opts.yaxis.labels.formatter = (new Function('return ' + opts.yaxis.labels.formatter))();
                }
                if (opts.tooltip?.custom && typeof opts.tooltip.custom === 'string') {
                    opts.tooltip.custom = (new Function('return ' + opts.tooltip.custom))();
                }
                return opts;
            },
            destroy() {
                if (this.chart) {
                    this.chart.destroy();
                    this.chart = null;
                }
            },
         }"
         x-init="init"
         x-on:livewire:navigating.window="destroy"></div>
</div>
