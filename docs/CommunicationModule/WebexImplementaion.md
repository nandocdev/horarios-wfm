### ✅ **Soluciones definitivas para Laravel 13**

#### **Opción 1: Usar el servicio directo con Guzzle (Recomendada)**

Esta es la solución más limpia y sin dependencias externas problemáticas.

**1. Crear el servicio Webex:**

```bash
php artisan make:service WebexService
```

**2. Crear `app/Services/WebexService.php`:**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebexService
{
    protected string $token;
    protected string $roomId;
    protected string $apiUrl;

    public function __construct()
    {
        $this->token = config('services.webex.bot_token');
        $this->roomId = config('services.webex.room_id');
        $this->apiUrl = 'https://webexapis.com/v1/messages';
    }

    /**
     * Enviar mensaje de texto simple
     */
    public function sendText(string $message, ?string $roomId = null): ?array
    {
        return $this->send([
            'roomId' => $roomId ?? $this->roomId,
            'text' => $message,
        ]);
    }

    /**
     * Enviar mensaje con formato Markdown
     */
    public function sendMarkdown(string $markdown, ?string $roomId = null): ?array
    {
        return $this->send([
            'roomId' => $roomId ?? $this->roomId,
            'markdown' => $markdown,
        ]);
    }

    /**
     * Enviar mensaje con mención a usuario
     */
    public function sendMention(string $message, string $personEmail, string $personName, ?string $roomId = null): ?array
    {
        $markdown = "Hola <@personEmail:$personEmail|$personName>, $message";
        return $this->sendMarkdown($markdown, $roomId);
    }

    /**
     * Enviar mensaje a todo el grupo
     */
    public function sendToAll(string $message, ?string $roomId = null): ?array
    {
        $markdown = "Hola <@all>, $message";
        return $this->sendMarkdown($markdown, $roomId);
    }

    /**
     * Enviar notificación con archivo adjunto (URL pública)
     */
    public function sendWithFile(string $message, string $fileUrl, ?string $roomId = null): ?array
    {
        return $this->send([
            'roomId' => $roomId ?? $this->roomId,
            'text' => $message,
            'files' => [$fileUrl],
        ]);
    }

    /**
     * Método privado para enviar la petición a Webex
     */
    private function send(array $payload): ?array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Error enviando mensaje a Webex', [
                'status' => $response->status(),
                'response' => $response->body(),
                'payload' => $payload,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Excepción al enviar mensaje a Webex', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return null;
        }
    }
}
```

**3. Configurar en `config/services.php`:**

```php
'webex' => [
    'bot_token' => env('WEBEX_BOT_TOKEN'),
    'room_id' => env('WEBEX_ROOM_ID'),
],
```

**4. Agregar a tu `.env`:**

```env
WEBEX_BOT_TOKEN=tu_token_del_bot_aqui
WEBEX_ROOM_ID=id_del_espacio_aqui
```

**5. Usar en tu aplicación:**

```php
<?php

namespace App\Http\Controllers;

use App\Services\WebexService;

class NotificationController extends Controller
{
    public function sendNotification(WebexService $webex)
    {
        // Mensaje simple
        $webex->sendText('Nuevo usuario registrado: Juan Pérez');

        // Mensaje con formato
        $webex->sendMarkdown('**Nuevo pago recibido**  \nMonto: *$1,500.00*  \nFactura: #1234');

        // Mencionar a alguien
        $webex->sendMention(
            'Tu tarea ha sido asignada',
            'juan.perez@empresa.com',
            'Juan Pérez'
        );

        // Mencionar a todo el equipo
        $webex->sendToAll('Despliegue completado a producción');

        return response()->json(['message' => 'Notificación enviada']);
    }
}
```

---

#### **Opción 2: Integración con el sistema de Notificaciones de Laravel**

Si quieres mantener el sistema de notificaciones, crea un canal personalizado:

**1. Crear el canal:**

```bash
php artisan make:notification WebexChannel
```

**2. Crear `app/Notifications/Channels/WebexChannel.php`:**

```php
<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use App\Services\WebexService;

class WebexChannel
{
    public function __construct(
        protected WebexService $webex
    ) {}

    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toWebex($notifiable);
        
        if (is_string($message)) {
            return $this->webex->sendText($message);
        }

        if (isset($message['markdown'])) {
            return $this->webex->sendMarkdown($message['markdown']);
        }

        return $this->webex->sendText($message['text'] ?? '');
    }
}
```

**3. Registrar en `config/app.php` (Providers):**

```php
'providers' => [
    // ...
    App\Providers\WebexNotificationServiceProvider::class,
],
```

**4. O crear el provider directamente:**

```bash
php artisan make:provider WebexNotificationServiceProvider
```

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Notifications\Channels\WebexChannel;
use App\Services\WebexService;

class WebexNotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WebexService::class, function ($app) {
            return new WebexService();
        });
    }

    public function boot(): void
    {
        // Registrar el canal de notificaciones
        $this->app->when(WebexChannel::class)
            ->needs(WebexService::class)
            ->give(function () {
                return app(WebexService::class);
            });
    }
}
```

**5. Usar en una notificación:**

```bash
php artisan make:notification InvoicePaid
```

```php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use App\Services\WebexService;

class InvoicePaid extends Notification
{
    public function __construct(protected $invoice)
    {}

    public function via($notifiable): array
    {
        return ['webex'];
    }

    public function toWebex($notifiable)
    {
        return [
            'markdown' => "**Factura #{$this->invoice->id} pagada**  \nMonto: *\${$this->invoice->amount}*  \nCliente: {$this->invoice->client_name}",
        ];
    }
}
```

**6. Enviar la notificación:**

```php
$user->notify(new InvoicePaid($invoice));
```

---

### 🎯 **Recomendación final**

Te sugiero **usar la Opción 1** (servicio directo con Guzzle) porque:
- ✅ No dependes de paquetes externos desactualizados
- ✅ Tienes control total sobre el código
- ✅ Es más fácil de mantener y actualizar
- ✅ Funciona con cualquier versión de Laravel
- ✅ Puedes extenderlo fácilmente con más funcionalidades

¿Te gustaría que implemente alguna de estas opciones o necesitas ayuda para obtener el token de Webex?