# LocationModule

## 🎯 Propósito
El `LocationModule` es un módulo de soporte que proporciona los catálogos geográficos necesarios para la plataforma. Está diseñado específicamente para la estructura político-administrativa de Panamá.

---

## 🚀 Funcionalidades Principales

### 1. Catálogo Geográfico Jerárquico
- **Provincias:** El nivel superior de división.
- **Distritos:** Subdivisión de las provincias.
- **Corregimientos (Townships):** El nivel más granular de ubicación, utilizado para las direcciones residenciales de los empleados.

### 2. Integración
- **EmployeesModule:** Proporciona los datos para validar y estandarizar las direcciones de los colaboradores.
- **API de Catálogos:** Rutas JSON para alimentar selectores dinámicos (cascada) en la interfaz de usuario (ej: seleccionar provincia → filtrar distritos → filtrar corregimientos).

---

## 🛠 Estructura Técnica

### Modelos Clave
- `Province`
- `District`
- `Township`

---

## 📋 Ejemplo de Uso

### Obtener corregimientos de un distrito
```php
$district = District::where('name', 'Panamá')->first();
$townships = $district->townships;
```
