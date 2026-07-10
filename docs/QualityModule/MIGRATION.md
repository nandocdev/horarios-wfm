# Plan de Migración — Esquema Legacy → Nuevo Esquema

## 1. Estrategia

Migración en 3 fases dentro de la misma base de datos `prueba`:

```
FASE 1: Crear nuevo esquema (tablas nuevas)
FASE 2: ETL — volcar datos legacy a tablas nuevas
FASE 3: Validar, renombrar tablas viejas, activar nuevo código
```

No se elimina ningún dato legacy hasta validación completa.

---

## 2. FASE 1 — Crear Nuevo Esquema

Ejecutar `DATABASE.sql` completo. Esto crea las 12 tablas nuevas + 2 vistas en `prueba`.

---

## 3. FASE 2 — ETL: Migrar Datos

### 3.1 Poblar `queues`

```sql
-- Las colas se insertan en DATABASE.sql. Solo verificar:
SELECT id, code, name FROM queues;
-- Debe devolver 11 filas (CM-Tr, CM-Canc, CM-Conf, AU, Farm, Mor, CONF, SIPE, WEB, CIGESA, Fact)
```

### 3.2 Migrar `Lista.php` → `users` + `user_roles`

Este paso requiere un script PHP porque los datos están en un archivo PHP (`Lista.php`), no en BD.

```php
<?php
// scripts/import_lista_usuarios.php
require_once __DIR__ . '/../config/database.php';

// Datos extraídos de Lista.php
$coordinadores = [
    ['login' => 'dafuentes',     'nombre' => 'Daniel Fuentes'],
    ['login' => 'dixguerrero',   'nombre' => 'Dixiana Guerrero'],
    ['login' => 'dreina',        'nombre' => 'Daira Reina'],
    ['login' => 'ericgonzalezv', 'nombre' => 'Eric Gonzalez'],
    ['login' => 'glguerra',      'nombre' => 'Glenis Guerra'],
    ['login' => 'khubner',       'nombre' => 'Karl Hubner'],
    ['login' => 'lcortez',       'nombre' => 'Lorena Cortez'],
    ['login' => 'loguevara',     'nombre' => 'Lorena Guevara'],
    ['login' => 'vquiros',       'nombre' => 'Valerie Quiros'],
    ['login' => 'vtejada',       'nombre' => 'Victor Tejada'],
    ['login' => 'yraman',        'nombre' => 'Yaremi Rahman'],
];
$supervisores = [
    ['login' => 'aarchibold',    'nombre' => 'Antonio Archibold'],
    ['login' => 'anbethancourt', 'nombre' => 'Ana Bethancourt'],
    ['login' => 'jmina',         'nombre' => 'Jose Mina'],
    ['login' => 'jospinzon',     'nombre' => 'Josselyn Pinzon'],
    ['login' => 'marielrodriguez','nombre' => 'Marielis Rodriguez'],
    ['login' => 'marlopez',      'nombre' => 'Marjoire Lopez'],
    ['login' => 'marvillarreal', 'nombre' => 'Marichel Villarreal'],
    ['login' => 'neriortega',    'nombre' => 'Neribel Ortega'],
    ['login' => 'nivaldes',      'nombre' => 'Nilsa Valdes'],
];
$evaluadores = [
    ['login' => 'hfranco',       'nombre' => 'Hernan Franco'],
    ['login' => 'kchan',         'nombre' => 'Katherine Chan'],
    ['login' => 'marmosquera',   'nombre' => 'Maria C. Mosquera'],
    ['login' => 'juanmendoza',   'nombre' => 'Juan C. Villarreal'],
];
$operadores = [
    ['login' => 'aigraell'], ['login' => 'arenteria'], /* ... 80+ más */
];

$pdo = new PDO(/* ... */);

foreach ([...] as $persona) {
    $stmt = $pdo->prepare('INSERT IGNORE INTO users (login, nombre) VALUES (?, ?)');
    $stmt->execute([$persona['login'], $persona['nombre']]);
    // Asignar rol según el arreglo de origen
}
```

### 3.3 Migrar `loginpass` → `login_credentials`

```sql
-- Migrar credenciales existentes (las contraseñas están en texto plano, se marcan para cambio forzoso)
INSERT INTO login_credentials (user_id, pass_hash, cambio, inhabilitado)
SELECT
    u.id,
    CONCAT('LEGACY_MIGRATED:', l.pass), -- Se obligará a cambiar en el primer login
    FALSE,  -- cambio = 0 → forzar cambio de contraseña
    l.inhabilitado
FROM prueba.loginpass l
JOIN users u ON u.login = l.login;
```

### 3.4 Migrar Tablas de Criterios (`*_eval` → `criteria` + `criteria_versions` + `queue_criteria`)

Cada tabla `*_eval` o `*_eval24-25` tiene la misma estructura: `id, criterio, puntaje, descripción`.

```sql
-- Ejemplo: migrar au_eval24-25 (Atención al Usuario, CIGESA y Facturación)
-- 1. Crear los criteria (identidad)
INSERT INTO criteria (code)
SELECT CONCAT('AU-', id) FROM prueba.`au_eval24-25`;

-- 2. Crear criteria_versions (versión 1, vigente desde el inicio del período)
INSERT INTO criteria_versions (criteria_id, version, criterio_text, puntaje, descripcion, valid_from)
SELECT
    c.id,
    1,
    a.criterio,
    a.puntaje,
    a.descripción,
    '2024-07-01'
FROM prueba.`au_eval24-25` a
JOIN criteria c ON c.code = CONCAT('AU-', a.id);

-- 3. Asignar a la cola AU
INSERT INTO queue_criteria (queue_id, criteria_versions_id, orden)
SELECT
    (SELECT id FROM queues WHERE code = 'AU'),
    cv.id,
    a.id
FROM prueba.`au_eval24-25` a
JOIN criteria c ON c.code = CONCAT('AU-', a.id)
JOIN criteria_versions cv ON cv.criteria_id = c.id AND cv.version = 1;

-- 4. Asignar los mismos criterios a CIGESA y Facturación (compartidos)
INSERT INTO queue_criteria (queue_id, criteria_versions_id, orden)
SELECT
    (SELECT id FROM queues WHERE code = 'CIGESA'),
    cv.id,
    a.id
FROM prueba.`au_eval24-25` a
JOIN criteria c ON c.code = CONCAT('AU-', a.id)
JOIN criteria_versions cv ON cv.criteria_id = c.id AND cv.version = 1;

INSERT INTO queue_criteria (queue_id, criteria_versions_id, orden)
SELECT
    (SELECT id FROM queues WHERE code = 'Fact'),
    cv.id,
    a.id
FROM prueba.`au_eval24-25` a
JOIN criteria c ON c.code = CONCAT('AU-', a.id)
JOIN criteria_versions cv ON cv.criteria_id = c.id AND cv.version = 1;
```

Repetir para cada tabla legacy. Mapa completo:

| Tabla legacy | Cola(s) | Prefijo code |
|---|---|---|
| `cm_tr_eval24-25` | CM-Tr | `CMTR-` |
| `cm_canc_eval24-25` | CM-Canc | `CMCAN-` |
| `cm_conf_eval24-25` | CM-Conf | `CMCONF-` |
| `au_eval24-25` | AU, CIGESA, Fact | `AU-` |
| `farm_eval24-25` | Farm | `FARM-` |
| `mor_eval` | Mor | `MOR-` |
| `conf_eval` | CONF | `CONF-` |
| `sipe_eval24-25` | SIPE | `SIPE-` |
| `web_eval25-26` | WEB | `WEB-` |
| `tbl_lab25-26` | (sin cola definida) | `LAB-` |

### 3.5 Migrar Red Flags

```sql
INSERT INTO red_flag_criteria (code, criterio_text, perdida, descripcion, is_active)
SELECT
    CONCAT('RF-', id),
    criterio,
    perdida,
    descripcion,
    TRUE
FROM prueba.`red_flag24-25`;
```

### 3.6 Migrar Evaluaciones (`teval` → `evaluations` + `evaluation_scores`)

Esta es la migración más crítica (~30K registros en `teval`, ~11K en `teval_mod`).

```sql
-- 3.6.1 Migrar cabeceras
INSERT INTO evaluations (
    id, logeval, logcoor, logsuper, loguser, queue_id,
    dtcall, tmcall, dteval, tmeval, score, callobs, has_redflag, status
)
SELECT
    t.id,
    t.logeval,
    t.logcoor,
    t.logsuper,
    t.loguser,
    COALESCE(q.id, 1),  -- si no hay match, asigna CM-Tr por defecto
    t.dtcall,
    t.tmcall,
    t.dteval,
    t.tmeval,
    t.score,
    t.callobs,
    CASE WHEN t.redflag = 1 THEN TRUE ELSE FALSE END,
    'activa'
FROM prueba.teval t
LEFT JOIN queues q ON q.code = t.tycall;
-- Nota: tycall almacena valores como 'CM-Tr', 'CM-Canc', etc.

-- 3.6.2 Migrar puntajes individuales (preg1..preg25 → evaluation_scores)
-- Requiere mapear el número de pregunta al criteria_versions_id correcto.
-- Esto depende de conocer el orden de los criterios en la tabla legacy.

-- Ejemplo para evaluaciones de tipo CM-Tr:
-- La tabla cm_tr_eval24-25 tiene 25 criterios en orden 1..25
-- El mapeo pregunta → criteria_versions.id se obtiene así:
WITH cmtr_criteria AS (
    SELECT
        ROW_NUMBER() OVER (ORDER BY qc.orden) AS num_pregunta,
        cv.id AS criteria_versions_id
    FROM queues q
    JOIN queue_criteria qc ON qc.queue_id = q.id
    JOIN criteria_versions cv ON cv.id = qc.criteria_versions_id
    WHERE q.code = 'CM-Tr'
    ORDER BY qc.orden
)
INSERT INTO evaluation_scores (evaluation_id, criteria_versions_id, puntaje_obtenido)
SELECT
    t.id,
    cm.criteria_versions_id,
    CASE cm.num_pregunta
        WHEN 1 THEN t.preg1 WHEN 2 THEN t.preg2 WHEN 3 THEN t.preg3
        WHEN 4 THEN t.preg4 WHEN 5 THEN t.preg5 /* ... hasta 25 */
    END
FROM prueba.teval t
JOIN cmtr_criteria cm ON cm.num_pregunta BETWEEN 1 AND 25
WHERE t.tycall = 'CM-Tr'
  AND CASE cm.num_pregunta
        WHEN 1 THEN t.preg1 WHEN 2 THEN t.preg2 /* ... */
      END IS NOT NULL;
```

> **Nota**: el script anterior debe repetirse para cada tycall, ajustando la CTE y los CASE WHEN. Se recomienda implementar esto como un script PHP que itere las evaluaciones y las inserte en evaluation_scores usando prepared statements para mejor rendimiento.

### 3.7 Migrar Calibraciones (`teval_mod` → `calibration_log`)

```sql
INSERT INTO calibration_log (evaluation_id, logcoor, score_anterior, score_nuevo, obs, dtch, tmch)
SELECT
    id,
    chlogcoor,
    chscore,
    chnwscore,
    chobs,
    dtch,
    tmch
FROM prueba.teval_mod
WHERE dtch < '3000-01-01';
-- Filtra los registros con fecha por defecto (sin calibración real)
```

### 3.8 Migrar Feedback (de `teval` + `feedback/`)

```sql
INSERT INTO feedback (evaluation_id, logfeed, obsfeed, dtfeed, tmfeed)
SELECT
    id,
    logcoor,  -- asume que el feedback lo da el coordinador
    obsfeed,
    dtfeed,
    tmfeed
FROM prueba.teval
WHERE feed = 1 AND dtfeed < '3000-01-01';
```

---

## 4. FASE 3 — Validación

```sql
-- Verificar que el total de evaluaciones coincide
SELECT COUNT(*) FROM prueba.teval WHERE status != 'eliminada';
SELECT COUNT(*) FROM evaluations;

-- Verificar que los puntajes totales coinciden
SELECT t.id, t.score AS score_legacy, e.score AS score_nuevo
FROM prueba.teval t
JOIN evaluations e ON e.id = t.id
WHERE t.score != e.score;

-- Verificar sumas de puntajes individuales
SELECT e.id, SUM(es.puntaje_obtenido) AS suma_scores, e.score
FROM evaluations e
JOIN evaluation_scores es ON es.evaluation_id = e.id
GROUP BY e.id
HAVING suma_scores != e.score;
```

---

## 5. Rollback

Si algo falla:

```sql
-- 1. Renombrar tablas nuevas como respaldo
RENAME TABLE evaluations TO evaluations_new;
RENAME TABLE evaluation_scores TO evaluation_scores_new;
-- ... etc.

-- 2. Restaurar nombres legacy (están intactos, nunca se tocaron)
-- Las tablas originales NO se modifican en ninguna fase.
```

---

## 6. Post-Migración

- [ ] Renombrar tablas legacy con prefijo `_legacy` (ej: `teval` → `teval_legacy`)
- [ ] Actualizar `conexion.php` para que apunte a las nuevas tablas (o eliminar y usar PDO)
- [ ] Reemplazar `Lista.php` por consultas a `users` + `user_roles`
- [ ] Actualizar cada formulario PHP para usar `v_queues_criteria_active`
- [ ] Actualizar `vistagen.php` para usar `v_evaluations_list` con server-side DataTables
- [ ] Ejecutar pruebas de regresión: evaluar, feedback, calibración, histórico
