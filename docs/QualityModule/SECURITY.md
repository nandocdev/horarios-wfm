# Lineamientos de Seguridad — Sistema de Evaluación de Llamadas

## 1. Estado Actual (Hallazgos)

| # | Hallazgo | Archivo | Severidad |
|---|---|---|---|
| S-01 | Contraseña de BD hardcodeada en texto plano | `conexion.php:6` | **CRÍTICA** |
| S-02 | SQL injection en todas las consultas (interpolación directa de `$_POST`) | `valpost.php`, `recibe.php`, `feedback.php`, etc. | **CRÍTICA** |
| S-03 | Contraseñas de usuarios en texto plano (`varchar(35)`) | `loginpass.pass` | **CRÍTICA** |
| S-04 | Sin autenticación obligatoria en páginas de formularios | `confir.php`, `citmed_*.php`, etc. | **ALTA** |
| S-05 | Sin CSRF tokens en formularios | Todos los `<form>` | **ALTA** |
| S-06 | Sin control de acceso por rol (cualquier usuario puede evaluar) | — | **ALTA** |
| S-07 | Datos sensibles de infraestructura expuestos (IP, usuario BD) | `conexion.php` | **MEDIA** |
| S-08 | Sin HTTPS forzado | — | **MEDIA** |
| S-09 | `$_SERVER` y `$_POST` se usan sin sanitizar | Varios | **MEDIA** |
| S-10 | `X-Powered-By: PHP` expuesto en headers | `php.ini` | **BAJA** |

---

## 2. Medidas Inmediatas (Mitigación Rápida)

### 2.1 Mover credenciales fuera del código

```php
// config/database.php — NUNCA subir a git
<?php
$db_config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'user' => getenv('DB_USER') ?: 'calidad_app',    // usuario con mínimos privilegios
    'pass' => getenv('DB_PASS'),                       // leer de variable de entorno
    'name' => getenv('DB_NAME') ?: 'prueba',
];
```

### 2.2 Prepared Statements (reemplazo inmediato para consultas críticas)

```php
// ❌ NUNCA MÁS:
mysqli_query($con, "SELECT * FROM loginpass WHERE login ='$loguser'");

// ✅ SIEMPRE:
$stmt = $pdo->prepare("SELECT * FROM loginpass WHERE login = ?");
$stmt->execute([$loguser]);
```

Reglas:
- **0% de interpolación** de variables en SQL, incluso si vienen de una base de datos.
- Usar exclusivamente PDO con `prepare()` + `execute()` o MySQLi con prepared statements.

### 2.3 Hash de contraseñas

```php
// Al registrar/cambiar contraseña:
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Al validar login:
if (password_verify($input_password, $stored_hash)) { /* ok */ }
```

Requisitos:
- Columna `pass_hash VARCHAR(255)` (bcrypt genera 60 chars, pero dejar margen).
- La contraseña por defecto `12345678` debe forzar cambio en el primer login (`cambio = FALSE`).

---

## 3. Arquitectura de Seguridad Propuesta

```
┌─────────────────────────────────────────────────────────────────────┐
│                        CAPA DE PRESENTACIÓN                         │
│  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌─────────────────┐ │
│  │  HTTPS    │  │  CSP      │  │  X-Frame  │  │  Cookie Secure  │ │
│  │  HSTS     │  │  Headers  │  │  Options  │  │  + HttpOnly     │ │
│  └───────────┘  └───────────┘  └───────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────────────┤
│                        CAPA DE APLICACIÓN                           │
│  ┌───────────┐  ┌───────────┐  ┌───────────┐  ┌─────────────────┐ │
│  │  Auth     │  │  CSRF     │  │  Input    │  │  Role-Based     │ │
│  │  Middleware│  │  Tokens   │  │  Validation│  │  Access Control │ │
│  └───────────┘  └───────────┘  └───────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────────────┤
│                        CAPA DE DATOS                                │
│  ┌───────────────┐  ┌───────────────┐  ┌─────────────────────────┐ │
│  │  Prepared     │  │  Password     │  │  Usuario BD con         │ │
│  │  Statements   │  │  Hashing      │  │  mínimos privilegios    │ │
│  └───────────────┘  └───────────────┘  └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 4. Checklist de Implementación

### 4.1 Autenticación y Sesión

- [ ] Usar `session_regenerate_id(true)` después de login exitoso (previene session fixation)
- [ ] Configurar `session.cookie_httponly = 1`, `session.cookie_secure = 1`, `session.cookie_samesite = "Strict"`
- [ ] Timeout de sesión: `session.gc_maxlifetime = 7200` (2 horas, ya configurado)
- [ ] Bloquear cuenta después de 3 intentos fallidos de login
- [ ] No revelar si el usuario existe o la contraseña es incorrecta (mensaje genérico: "Credenciales inválidas")

### 4.2 Control de Acceso

- [ ] Cada página debe verificar que el usuario tiene sesión activa:
    ```php
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    ```
- [ ] Cada acción debe verificar el rol mínimo requerido:
    ```php
    if (!in_array('evaluador', $_SESSION['roles'])) {
        http_response_code(403);
        exit;
    }
    ```
- [ ] El menú principal solo debe mostrar opciones según el rol del usuario

### 4.3 Validación de Input

| Tipo | Regla | Ejemplo |
|---|---|---|
| Strings | `filter_input(INPUT_POST, 'field', FILTER_SANITIZE_STRING)` | nombres, observaciones |
| Enteros | `filter_input(INPUT_POST, 'score', FILTER_VALIDATE_INT)` | puntajes, IDs |
| Fechas | Validar formato `YYYY-MM-DD` vía `DateTime::createFromFormat()` | fecha llamada, fecha evaluación |
| Emails | `filter_var($email, FILTER_VALIDATE_EMAIL)` | — |
| Selects | Validar contra lista blanca de valores permitidos | tipo_llamada, roles |

### 4.4 CSRF

En cada formulario:

```php
// Generar token al cargar el formulario
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Validar al recibir
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    http_response_code(419); // Session expired / CSRF
    exit;
}
```

### 4.5 Headers HTTP

```apache
# .htaccess
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "DENY"
Header always set X-XSS-Protection "1; mode=block"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Content-Security-Policy "default-src 'self'; style-src 'self' https: 'unsafe-inline'; script-src 'self' https: 'unsafe-inline'"
```

### 4.6 Base de Datos

- [ ] Crear usuario de BD dedicado para la aplicación (no `root`, no `Generico`)
- [ ] Conceder SOLO `SELECT, INSERT, UPDATE, DELETE` en `prueba.*`
- [ ] No conceder `DROP`, `ALTER`, `CREATE` al usuario de la aplicación
- [ ] Las migraciones y cambios de esquema se ejecutan con un usuario admin diferente

### 4.7 Logging

```php
class AuditLog {
    public static function log($action, $details, $userId = null) {
        $log = sprintf(
            "[%s] [user:%s] [ip:%s] %s: %s\n",
            date('Y-m-d H:i:s'),
            $userId ?? 'anon',
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $action,
            json_encode($details, JSON_UNESCAPED_UNICODE)
        );
        error_log($log, 3, __DIR__ . '/../logs/audit.log');
    }
}

// Eventos a loguear:
// - Login exitoso / fallido
// - Evaluación creada / eliminada
// - Feedback agregado
// - Calibración realizada
// - Cambio de contraseña
// - Creación/modificación de criterios
```

---

## 5. Checklist de Revisión Pre-Release

- [ ] ¿No hay credenciales en el código?
- [ ] ¿Todas las consultas SQL usan prepared statements?
- [ ] ¿Todas las contraseñas están hasheadas con bcrypt?
- [ ] ¿Hay CSRF tokens en todos los formularios?
- [ ] ¿Cada endpoint verifica autenticación?
- [ ] ¿Cada endpoint verifica autorización (rol)?
- [ ] ¿Los headers de seguridad están configurados?
- [ ] ¿El usuario de BD tiene solo los privilegios necesarios?
- [ ] ¿Las sesiones tienen timeout configurado?
- [ ] ¿Los mensajes de error son genéricos (no revelan detalles internos)?

---

## 6. Vulnerabilidades Conocidas (Post-Migración)

| ID | Descripción | Prioridad |
|---|---|---|
| V-01 | Sin HTTPS en producción — las credenciales viajan en texto plano | **Alta** |
| V-02 | Sin 2FA — una contraseña comprometida expone todo el sistema | **Media** |
| V-03 | Sin rate limiting en login — permite fuerza bruta | **Media** |
| V-04 | Sin auditoría automatizada — los logs se revisan manualmente | **Baja** |
