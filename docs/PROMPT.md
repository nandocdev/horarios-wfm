Eres un analista de sistemas experto en ingeniería de requerimientos y documentación técnica.  
Tu tarea es analizar **cada módulo** de la aplicación listado en el árbol de directorios y generar un documento de especificaciones técnicas completo.

## Instrucciones generales

1. **Entrada:**  
   Trabajemos sobre el modulo **WorkflowsModule**.  
   Debes asumir que dicho módulo existe dentro de `app/Modules/` y que contiene las subcarpetas estándar (Actions, DTOs, Models, Livewire, Routes, etc.).

2. **Objetivo:**  
   Generar un documento Markdown en la ruta `docs/RUP/{NombreModulo}.md` que contenga:

   - **Propósito del módulo**  
   - **Casos de uso detallados** (diagrama o listado)  
   - **Requerimientos funcionales**  
   - **Requerimientos no funcionales** (rendimiento, seguridad, auditoría)  
   - **Modelos de datos detallados** (entidades y relaciones)  
   - **Roles y permisos** (relacionados con Policies)  
   - **Eventos, listeners y notificaciones** (si aplica)  
   - **Servicios y acciones detallados**  
   - **Endpoints o rutas detallados** (HTTP y/o Livewire)  
   - **Dependencias con otros módulos**  

   **Nota:** Cada uno de los puntos debe ser documentado de manera detallada. procurando abarcar todos los aspectos posibles del módulo.

3. **Metodología sugerida:**  
   - Infiere responsabilidades según el nombre del módulo y su estructura.  
   - Propón funcionalidades típicas de ese dominio (ej. PersonnelModule: gestión de empleados, contratos, organigrama).  
   - Usa un enfoque orientado a dominio (DDD) y buenas prácticas de Laravel.  
   - Documenta en español, con claridad y ejemplos concretos.

4. **Formato de salida:**  
   - Archivo: `docs/RUP/{NombreModulo}.md`  
   - Usa títulos, subtítulos, listas, tablas y bloques de código cuando sea necesario.  
   - Incluye un resumen ejecutivo al inicio.

5. **Proceso paso a paso para cada módulo:**  

   - **Paso 1:** Analiza la carpeta del módulo y su estructura interna.  
   - **Paso 2:** Define el alcance y propósito del módulo.  
   - **Paso 3:** Identifica entidades detallados (Models).  
   - **Paso 4:** Describe casos de uso (acciones del usuario y del sistema).  
   - **Paso 5:** Enumera requerimientos funcionales y no funcionales.  
   - **Paso 6:** Especifica interacciones con otros módulos (ej. PersonnelModule usa CoreModule para notificaciones).  
   - **Paso 7:** Genera el archivo Markdown con toda la información.

---
