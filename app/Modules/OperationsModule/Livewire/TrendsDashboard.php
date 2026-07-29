<?php

declare(strict_types=1);

namespace App\Modules\OperationsModule\Livewire;

use App\Modules\AnalyticsModule\Models\DailyKpi;
use Livewire\Component;

class TrendsDashboard extends Component
{
    public string $period = 'weekly';

    public string $metric = 'occupancy';

    public int $weeks = 12;

    public function render()
    {
        $since = match ($this->period) {
            'daily' => now()->subDays(30)->toDateString(),
            'weekly' => now()->subWeeks($this->weeks)->toDateString(),
            'monthly' => now()->subMonths(12)->toDateString(),
        };

        $globalKpis = DailyKpi::where('granularity', 'global')
            ->where('evaluation_date', '>=', $since)
            ->orderBy('evaluation_date')
            ->get();

        $trends = $this->aggregateByPeriod($globalKpis);

        $metricMeta = $this->metricMeta($this->metric);
        $periodOptions = ['daily' => 'Diario', 'weekly' => 'Semanal', 'monthly' => 'Mensual'];
        $metricOptions = [
            'occupancy' => 'Occupancy',
            'utilization' => 'Utilization',
            'productivity' => 'Productivity',
            'adherence' => 'Adherence',
            'conformance' => 'Conformance',
            'shrinkage_pct' => 'Shrinkage',
            'aht_seconds' => 'AHT',
            'asa_seconds' => 'ASA',
            'service_level' => 'Service Level',
            'fcr_pct' => 'FCR',
            'total_calls' => 'Llamadas',
        ];

        return view('operations::livewire.trends-dashboard', [
            'trends' => $trends,
            'metricMeta' => $metricMeta,
            'periodOptions' => $periodOptions,
            'metricOptions' => $metricOptions,
        ])->layout('layouts.app', ['title' => 'Tendencias']);
    }

    private function aggregateByPeriod($kpis): array
    {
        if ($this->period === 'daily') {
            return $kpis->map(fn ($k) => [
                'label' => $k->evaluation_date?->format('d/m') ?? '—',
                'value' => $this->extractValue($k),
                'sort' => $k->evaluation_date?->toDateString() ?? '',
            ])->toArray();
        }

        $grouped = $kpis->groupBy(fn ($k) => match ($this->period) {
            'weekly' => $k->evaluation_date?->isoWeekYear().'-W'.str_pad((string) ($k->evaluation_date?->isoWeek() ?? 0), 2, '0', STR_PAD_LEFT),
            'monthly' => $k->evaluation_date?->format('Y-m') ?? '',
        });

        return $grouped->map(function ($items, $key) {
            $values = $items->map(fn ($k) => $this->extractValue($k))->filter(fn ($v) => $v !== null);

            return [
                'label' => $key,
                'value' => $values->isNotEmpty() ? round($values->avg(), 1) : null,
                'sort' => $key,
            ];
        })->sortBy('sort')->values()->toArray();
    }

    private function extractValue($kpi): ?float
    {
        $field = $this->metric;

        if ($field === 'aht_seconds' || $field === 'asa_seconds') {
            return $kpi->$field ? round((float) $kpi->$field) : null;
        }

        return $kpi->$field !== null ? (float) $kpi->$field : null;
    }

    private function metricMeta(string $metric): array
    {
        return match ($metric) {
            'occupancy', 'utilization', 'productivity', 'adherence', 'conformance' => [
                'suffix' => '%',
                'higher' => 'better',
                'good' => 85,
                'bad' => 70,
            ],
            'shrinkage_pct' => [
                'suffix' => '%',
                'higher' => 'worse',
                'good' => 20,
                'bad' => 30,
            ],
            'service_level', 'fcr_pct' => [
                'suffix' => '%',
                'higher' => 'better',
                'good' => 80,
                'bad' => 60,
            ],
            'aht_seconds', 'asa_seconds' => [
                'suffix' => 's',
                'higher' => 'worse',
                'good' => 180,
                'bad' => 300,
            ],
            'total_calls' => [
                'suffix' => '',
                'higher' => 'neutral',
                'good' => null,
                'bad' => null,
            ],
            default => ['suffix' => '', 'higher' => 'neutral', 'good' => null, 'bad' => null],
        };
    }
}
