import './bootstrap';
import './tours';
import ApexCharts from 'apexcharts';

window.ApexCharts = ApexCharts;

const escapeHtml = (value) => String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

const chartCallbacks = {
    percent: () => (value) => `${value}%`,
    seconds: () => (value) => `${Number(value).toFixed(0)}s`,
    aht: () => (value) => value ? `${Number(value).toFixed(1)}s` : '-',
    integer: () => (value) => Number.isInteger(value) ? value : '',
    calls: () => (value) => Number.isInteger(value) ? `${value} llamadas` : value,
    agentCount: () => (value) => `${value} agentes`,
    totalMinutes: () => (widget) => `${widget.globals.seriesTotals.reduce((total, value) => total + value, 0)} min`,
    agentTotal: () => (widget) => widget.globals.seriesTotals.reduce((total, value) => total + value, 0),
    timeOfDay: ({ shiftStart = 0 } = {}) => (value) => {
        let totalMinutes = shiftStart + Number(value);
        if (totalMinutes < 0) {
            totalMinutes += 1440;
        }

        const hours = Math.floor(totalMinutes / 60) % 24;
        const minutes = Math.floor(totalMinutes % 60);

        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    },
    scatterTooltip: ({ shiftStart = 0 } = {}) => ({ seriesIndex, dataPointIndex, w }) => {
        const point = w.config.series[seriesIndex].data[dataPointIndex];
        const time = chartCallbacks.timeOfDay({ shiftStart })(point.x);

        return `<div class="px-3 py-2 text-xs">`
            + `<strong>${escapeHtml(w.config.series[seriesIndex].name)}</strong><br>`
            + `Hora: ${time}<br>`
            + `Talk Time: ${escapeHtml(point.y)}s`
            + '</div>';
    },
    legendTotal: () => (seriesName, opts) => {
        const total = opts.w.globals.series[opts.seriesIndex].reduce((a, b) => a + b, 0);
        return `${seriesName} (total: ${total})`;
    },
};

window.ChartCallbacks = chartCallbacks;

const clone = (value) => {
    if (typeof structuredClone === 'function') {
        try {
            return structuredClone(value);
        } catch {
            // Alpine wraps x-data objects in Proxy instances that structuredClone cannot copy.
        }
    }

    return JSON.parse(JSON.stringify(value));
};

const resolveCallbacks = (value) => {
    if (Array.isArray(value)) {
        return value.map(resolveCallbacks);
    }

    if (value && typeof value === 'object') {
        if (value.__callback) {
            const callbackFactory = chartCallbacks[value.__callback];

            if (! callbackFactory) {
                throw new Error(`Callback ApexCharts no registrado: ${value.__callback}`);
            }

            return callbackFactory(value.params ?? {});
        }

        return Object.fromEntries(
            Object.entries(value).map(([key, item]) => [key, resolveCallbacks(item)]),
        );
    }

    return value;
};

const apexChart = (encodedOptions) => ({
    chart: null,
    resizeObserver: null,
    baseOptions: JSON.parse(atob(encodedOptions)),

    init() {
        this.create();

        if (typeof ResizeObserver !== 'undefined') {
            this.resizeObserver = new ResizeObserver(() => this.resize());
            this.resizeObserver.observe(this.$el);
        }
    },

    buildOptions() {
        return resolveCallbacks(clone(this.baseOptions));
    },

    create() {
        this.destroyChart();

        try {
            this.chart = new ApexCharts(this.$el, this.buildOptions());
            this.chart.render();
        } catch (error) {
            this.chart = null;
            console.error('[ApexChart] No se pudo renderizar el gráfico.', error);
        }
    },

    update(data = {}) {
        if (! this.chart) {
            this.create();

            return;
        }

        try {
            const options = {};

            if (data.categories) {
                options.xaxis = { categories: data.categories };
            }

            if (data.annotations) {
                options.annotations = data.annotations;
            }

            if (data.title) {
                options.title = { text: data.title };
            }

            if (data.legend) {
                options.legend = data.legend;
            }

            this.chart.updateOptions(options, false, true);

            if (data.series) {
                this.chart.updateSeries(data.series, false);
            }
        } catch (error) {
            console.error('[ApexChart] No se pudo actualizar el gráfico.', error);
        }
    },

    updateSeries(series, categories = null) {
        this.update({ series, categories });
    },

    resize() {
        if (! this.chart) {
            return;
        }

        try {
            this.chart.updateOptions({
                chart: { width: this.$el.clientWidth },
            }, false, false);
        } catch (error) {
            console.error('[ApexChart] No se pudo redimensionar el gráfico.', error);
        }
    },

    refresh() {
        if (! this.chart) {
            this.create();

            return;
        }

        try {
            this.chart.updateOptions(this.buildOptions(), false, true);
        } catch (error) {
            console.error('[ApexChart] No se pudo refrescar el gráfico.', error);
        }
    },

    destroyChart() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    },

    destroy() {
        this.resizeObserver?.disconnect();
        this.resizeObserver = null;
        this.destroyChart();
    },
});

window.ChartComponent = apexChart;

let alpineRegistered = false;
const registerApexChart = () => {
    if (! window.Alpine || alpineRegistered) {
        return;
    }

    window.Alpine.data('apexChart', apexChart);
    alpineRegistered = true;
};

document.addEventListener('alpine:init', registerApexChart);
registerApexChart();
