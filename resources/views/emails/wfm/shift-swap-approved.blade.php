<x-mail::message>
# Cambio de Turno Aprobado

Hola {{ $recipient_user->first_name }},

Te informamos que la solicitud de cambio de turno para la fecha **{{ $swap->start_date->format('d/m/Y') }}** @if($swap->end_date && $swap->end_date->ne($swap->start_date)) al **{{ $swap->end_date->format('d/m/Y') }}** @endif ha sido aprobada y procesada por WFM (**{{ $approver->full_name }}**).

### Detalles del Cambio:
- **Solicitante:** {{ $swap->requester->full_name }}
- **Receptor:** {{ $swap->recipient->full_name }}
- **Fecha:** {{ $swap->start_date->format('d/m/Y') }} @if($swap->end_date && $swap->end_date->ne($swap->start_date)) al {{ $swap->end_date->format('d/m/Y') }} @endif

<x-mail::panel>
**Nota de Coordinación Operativa:**
Por efectos de este cambio de turno, para la fecha indicada, cada colaborador queda bajo la supervisión y coordinación del coordinador de su compañero de intercambio. Esto asegura la continuidad operativa y el seguimiento de las métricas en tiempo real.
</x-mail::panel>

Por favor, asegúrate de revisar tu horario actualizado en la plataforma.

<x-mail::button :url="route('schedules.my-schedule')">
Ver Mi Horario
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
