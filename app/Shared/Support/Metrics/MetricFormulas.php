<?php

declare(strict_types=1);

namespace App\Shared\Support\Metrics;

/**
 * Librería de fórmulas estandarizadas para el cálculo de métricas de desempeño.
 * Asegura que todos los módulos utilicen la misma lógica matemática.
 */
final class MetricFormulas {
    /**
     * Calcula el porcentaje de productividad.
     * Intensidad del trabajo mientras el agente estuvo conectado.
     */
    public static function productivity(float $productiveMinutes, float $connectedMinutes): float {
        if ($connectedMinutes <= 0) {
            return 0.0;
        }

        return round(($productiveMinutes / $connectedMinutes) * 100, 1);
    }

    /**
     * Calcula el porcentaje de utilización.
     * Rendimiento contra lo planificado (ajustado por tiempo transcurrido si es hoy).
     */
    public static function utilization(float $productiveMinutes, float $baseMinutes): float {
        if ($baseMinutes <= 0) {
            // Si el agente está produciendo pero no tiene tiempo base (ej. antes del turno), 
            // la utilización técnica es 100% de su tiempo transcurrido (o 0 si queremos castigar extra-jornada).
            // Por simplicidad para el dashboard: si hay producción pero no base, devolvemos 100% 
            // solo si queremos incentivar la conexión temprana, o 0 si medimos apego estricto.
            return $productiveMinutes > 0 ? 100.0 : 0.0;
        }

        $rate = ($productiveMinutes / $baseMinutes) * 100;
        
        return round(min($rate, 100.0), 1); // Capeamos al 100% para evitar distorsiones
    }

    /**
     * Calcula el tiempo base (denominador) para la utilización.
     * Si es el día de hoy y el turno está en curso, devuelve los minutos transcurridos.
     * De lo contrario, devuelve los minutos totales del turno.
     */
    public static function utilizationDenominator(
        int $scheduledMinutes,
        bool $isToday,
        ?\DateTimeInterface $startTime = null,
        ?\DateTimeInterface $endTime = null
    ): int {
        if (!$isToday || $startTime === null || $endTime === null) {
            return max(1, $scheduledMinutes);
        }

        $now = new \DateTimeImmutable();

        // Si el turno aún no empieza
        if ($now < $startTime) {
            return 0; // El turno no ha iniciado, la base es cero.
        }

        // Si el turno ya terminó
        if ($now > $endTime) {
            return max(1, $scheduledMinutes);
        }

        // Si estamos en curso del turno
        $elapsed = (int) floor(($now->getTimestamp() - $startTime->getTimestamp()) / 60);

        return max(1, min($scheduledMinutes, $elapsed));
    }

    /**
     * Determina si una marca de tiempo constituye una tardanza.
     */
    public static function checkLate(string|\DateTimeInterface $scheduledEntry, string|\DateTimeInterface $actualEntry, int $graceMinutes = 5): bool {
        $scheduled = $scheduledEntry instanceof \DateTimeInterface 
            ? $scheduledEntry 
            : new \DateTimeImmutable($scheduledEntry);

        $actual = $actualEntry instanceof \DateTimeInterface 
            ? $actualEntry 
            : new \DateTimeImmutable($actualEntry);

        $diffMinutes = ($actual->getTimestamp() - $scheduled->getTimestamp()) / 60;

        return $diffMinutes > $graceMinutes;
    }

    /**
     * Calcula el AHT (Average Handle Time).
     */
    public static function aht(float $totalTalkTime, float $totalWorkTime, int $totalCalls): float {
        if ($totalCalls <= 0) {
            return 0.0;
        }

        return round(($totalTalkTime + $totalWorkTime) / $totalCalls);
    }

    /**
     * Convierte segundos a minutos con precisión configurable.
     */
    public static function secondsToMinutes(int $seconds, int $precision = 1): float {
        return round($seconds / 60, $precision);
    }

    /**
     * Formatea una duración en segundos al formato HH:MM:SS.
     */
    public static function formatDuration(int $seconds): string {
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    /**
     * Verifica la adherencia de un estado real frente a un tipo esperado.
     */
    public static function checkAdherence(?string $realState, ?string $expectedType): bool {
        $real = strtoupper($realState ?? 'OFFLINE');
        $isLogoutOrOffline = in_array($real, ['OFFLINE', 'LOGOUT', 'LOGGED_OUT', 'UNKNOWN']);
        $productiveStates = ['READY', 'TALKING', 'WORK', 'RESERVED', 'HOLD', 'OUTBOUND'];

        // Si el usuario está desconectado, no evaluamos adherencia negativa aquí
        if ($isLogoutOrOffline) {
            return true;
        }

        // Si está conectado pero debería estar fuera de jornada -> No Adherente
        if ($expectedType === 'OFF') {
            return false;
        }

        if ($expectedType === 'SHIFT') {
            return in_array($real, $productiveStates);
        }

        if ($expectedType === 'INTRADAY' || $expectedType === 'EXCEPTION') {
            // Se espera que esté en NOT_READY (auxiliar) para actividades o excepciones
            return $real === 'NOT_READY';
        }

        return false;
    }

    /**
     * Calcula la Cobertura Operativa (Coverage Rate).
     */
    public static function coverageRate(int $availableAgents, int $scheduledAgents): float {
        if ($scheduledAgents <= 0) {
            return 0.0;
        }

        return round(($availableAgents / $scheduledAgents) * 100, 1);
    }

    /**
     * Calcula la Tasa de Ausentismo.
     */
    public static function absenteeismRate(float $absentMinutes, float $scheduledProductiveMinutes): float {
        if ($scheduledProductiveMinutes <= 0) {
            return 0.0;
        }

        return round(($absentMinutes / $scheduledProductiveMinutes) * 100, 1);
    }

    /**
     * Calcula la cantidad de personal ausente (Headcount).
     * 
     * @param int $scheduled Cantidad de personal programado para estar PRODUCTIVO 
     *                       (Turno activo y SIN excepciones aprobadas).
     * @param int $actual Cantidad de personal que efectivamente está conectado/presente.
     */
    public static function absentPersonnel(int $scheduled, int $actual): int {
        return max(0, $scheduled - $actual);
    }

    /**
     * Calcula la Ocupación (Occupancy).
     * Presión operativa sobre el tiempo disponible real (excluyendo auxiliares).
     */
    public static function occupancy(
        float $talkTime,
        float $holdTime,
        float $workTime,
        float $totalLoggedTime,
        float $auxTime
    ): float {
        $denominator = $totalLoggedTime - $auxTime;
        if ($denominator <= 0) {
            return 0.0;
        }

        return round((($talkTime + $holdTime + $workTime) / $denominator) * 100, 1);
    }

    /**
     * Calcula el Conformance.
     * Cumplimiento de la cantidad total de tiempo programada.
     */
    public static function conformance(float $actualWorkedMinutes, float $scheduledWorkedMinutes): float {
        if ($scheduledWorkedMinutes <= 0) {
            return 0.0;
        }

        return round(($actualWorkedMinutes / $scheduledWorkedMinutes) * 100, 1);
    }

    /**
     * Calcula el ASA (Average Speed of Answer).
     */
    public static function asa(float $totalQueueWaitTime, int $answeredCalls): float {
        if ($answeredCalls <= 0) {
            return 0.0;
        }

        return round($totalQueueWaitTime / $answeredCalls, 1);
    }

    /**
     * Calcula el Service Level (Nivel de Servicio).
     */
    public static function serviceLevel(int $callsWithinThreshold, int $totalOfferedCalls): float {
        if ($totalOfferedCalls <= 0) {
            return 0.0;
        }

        return round(($callsWithinThreshold / $totalOfferedCalls) * 100, 1);
    }
}
