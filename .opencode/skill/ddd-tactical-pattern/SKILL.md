---
name: ddd-tactical-pattern
description: Usar al crear un Aggregate Root, Entity o Value Object nuevo en Scheduling, WorkforceRequests o ContactCenterOps. No aplica a contexts Supporting/Generic.
---

# Patrón: Aggregate Root en este proyecto

## Estructura mínima
- Constructor privado + named constructor estático (`::create(...)`)
- Métodos de comportamiento que validan invariantes ANTES de mutar estado
- Eventos de dominio recolectados en el agregado, despachados por el repo al persistir
- CERO dependencias de Illuminate

## Ejemplo (referencia, no copiar literal)
```php
final class WeeklySchedule
{
    private array $domainEvents = [];

    private function __construct(
        private readonly WeeklyScheduleId $id,
        private ScheduleStatus $status,
        private array $assignments,
    ) {}

    public static function create(WeeklyScheduleId $id): self { /* ... */ }

    public function publish(): void
    {
        if (! $this->hasNoUncoveredWindows()) {
            throw new CannotPublishScheduleWithGaps($this->id);
        }
        $this->status = ScheduleStatus::Published;
        $this->domainEvents[] = new WeeklySchedulePublished($this->id);
    }

    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}
```

## Repository interface (va en Domain/, implementación en Infrastructure/)
```php
interface WeeklyScheduleRepositoryInterface
{
    public function find(WeeklyScheduleId $id): ?WeeklySchedule;
    public function save(WeeklySchedule $schedule): void;
}
```