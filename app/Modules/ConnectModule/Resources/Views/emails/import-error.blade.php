<x-mail::message>
# Error en Procesamiento UCCX

Se ha detectado un problema al intentar importar un archivo de datos de Cisco UCCX.

**Archivo:** {{ $fileName }}
**Error:** {{ $errorMessage }}

<x-mail::panel>
{{ $stackTrace }}
</x-mail::panel>

Por favor, verifique el estado del servidor de archivos y la estructura del CSV mencionado.

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
