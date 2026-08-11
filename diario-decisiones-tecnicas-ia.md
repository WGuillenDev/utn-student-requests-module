# Diario de Decisiones Técnicas e IA — SIGA-UTN

Documento donde el equipo registra, a lo largo de todo el desarrollo: qué se le consultó a la IA, qué se aceptó de su respuesta, qué se rechazó y por qué, qué hubo que corregir porque estaba incorrecto o incompleto, y qué se aprendió del proceso.

Este diario es la evidencia de que el criterio técnico del equipo, y no la herramienta, dirigió las decisiones del proyecto. Un diario vacío o genérico ("la IA ayudó con el código") no cumple el requisito.

---

## Entrada — 10 de agosto de 2026
**Tema:** Integración del módulo de Solicitudes Estudiantiles (ES-01–ES-04) al proyecto SIGA-UTN
**Participantes:** Equipo de desarrollo ISW-521
**Herramienta consultada:** Claude (Anthropic), vía chat con acceso a archivos del proyecto

---

### 1. Qué se le consultó a la IA

Se le pidió que analizara dos archivos comprimidos:
- `utn-student-requests-module.zip`: un prototipo aislado del módulo de Solicitudes Estudiantiles (portal, motor de reglas, bandeja centralizada), construido a partir de los requerimientos FR-ES-01 a FR-ES-04 del SRS v1.2.
- `SIGA-UTN.zip`: el proyecto real del equipo, con su propia arquitectura ya establecida (contexto `IdentityAccess` bajo DDD granular en `src/`).

La instrucción fue: *"analiza ambos .zip y copia todo lo correspondiente al módulo de Solicitudes Estudiantiles al proyecto de SIGA-UTN."*

### 2. Qué encontró la IA antes de actuar (y por qué no copió directo)

Antes de mover un solo archivo, la IA reportó tres problemas que una copia mecánica habría ocultado hasta que fallara en `php artisan migrate` o en producción:

1. **Dependencias faltantes:** las tablas de Solicitudes (`solicitudes`, `reglas_levantamiento`, etc.) dependen por FK de tablas de otros módulos (`cursos`, `estudiantes`, `periodos_academicos`, `historial_academico`) que **no existen** en SIGA-UTN, porque ese proyecto solo tiene construido el contexto `IdentityAccess` hasta ahora.
2. **Convención de nombres inconsistente:** el prototipo usa nombres de tabla/columna en español (`solicitudes`, `curso_id`, `estudiante_id`), mientras que todo lo ya construido en SIGA-UTN usa inglés (`permissions`, `roles`, `module`).
3. **Arquitectura distinta:** SIGA-UTN organiza el código por contexto acotado (`src/<BoundedContext>/<Aggregate>/{Domain,Application,Infrastructure,Presentation}`), mientras que el prototipo usa capas planas (`app/Domain`, `app/Application`, `app/Infrastructure`) — y, según su propio README, **solo la capa Infrastructure está realmente construida**; Domain y Application son carpetas vacías con un README.

Esto se registra porque es el punto central del ejercicio: la IA no ejecutó la instrucción literal ("copia todo") sin cuestionarla. Detuvo el trabajo y presentó las tres decisiones al equipo antes de generar una sola línea de código.

### 3. Qué se aceptó de la respuesta de la IA

- El diagnóstico de las tres inconsistencias (dependencias, idioma, arquitectura) se aceptó íntegramente: coincide con lo que el equipo hubiera encontrado al intentar correr las migraciones.
- La propuesta de **traducir todo a inglés** para no introducir una mezcla de idiomas en el esquema, dado que ya existe una convención establecida en el proyecto real.
- La propuesta de **copiar también las tablas prerrequisito** (`careers`, `academic_periods`, `study_plans`, `levels`, `courses`, `course_level`, `students`, `student_study_plan`, `academic_records`), aunque no pertenecen estrictamente al alcance de Solicitudes, porque sin ellas el módulo no es funcional.
- El código generado para las 9 migraciones nuevas, 12 modelos Eloquent, 12 factories y 2 seeders, tras revisión manual de nombres de tabla, tipos de columna y relaciones contra la migración y los modelos originales del prototipo.

### 4. Qué se rechazó y por qué

- **Se rechazó adaptar ya mismo la estructura al patrón DDD de SIGA-UTN** (`src/Requests/...` con Domain/Application/Infrastructure/Presentation completos). Razón: el prototipo no tiene esas capas construidas — hacerlo habría significado que la IA *inventara* reglas de negocio, casos de uso y componentes Livewire desde cero, sin que existiera una versión de referencia que validar. El equipo prefirió copiar únicamente lo que existe (Infrastructure) y diseñar Domain/Application propios en una iteración posterior, con criterio del equipo y no generado de cero por la IA.
- **Se rechazó copiar los modelos `Rbac/Models` (Permission/Role)** del prototipo. SIGA-UTN ya tiene su propia implementación de roles y permisos, tanto en `App\Models\Role/Permission` como en el contexto DDD `src/IdentityAccess`. Copiar la versión del prototipo habría creado un modelo paralelo y conflictivo para la misma responsabilidad.
- **Se rechazó copiar `DomainServiceProvider.php`** del prototipo: es un *stub* vacío pensado para registrar bindings de interfaces de Domain que, como se explicó arriba, no existen. Incluirlo no aportaba nada funcional y hubiera quedado como código muerto.

### 5. Qué hubo que corregir o verificar manualmente

- La IA no tuvo acceso a un intérprete de PHP en este entorno, por lo que **no pudo ejecutar `php artisan migrate` ni las pruebas** para validar el resultado. El equipo debe correr las migraciones en un entorno local/CI antes de dar esto por cerrado; la IA lo señaló explícitamente en vez de dar por buena la integración solo por el chequeo estático que sí hizo (balance de llaves, búsqueda de residuos en español).
- Se verificó manualmente que ningún nombre de tabla nuevo (`requests`, `students`, `courses`, etc.) colisionara con nombres reservados o con algo ya existente en el proyecto — la IA no tenía forma de comprobar esto contra una base de datos real.
- Quedó pendiente de decisión del equipo (no resuelto por la IA) el campo `academic_records.equivalence_id`, que no tiene FK real porque depende de un futuro módulo de Repositorio Curricular fuera de alcance. La IA lo dejó documentado en un comentario dentro de la migración en lugar de inventar una tabla `equivalences` que no fue solicitada.

### 6. Qué se aprendió del proceso

- Pedir a una IA "copia todo el módulo X" sin especificar destino/convenciones es una instrucción ambigua que puede producir una integración que compila pero no es coherente con el resto del proyecto. Vale la pena, como equipo, anticipar y decidir explícitamente el idioma y la arquitectura destino *antes* de pedir la integración, no después.
- Un prototipo aislado (el `.zip` del módulo de Solicitudes) puede tener piezas construidas de forma desigual (solo Infrastructure, sin Domain/Application). Es responsabilidad del equipo revisar el estado real de lo que se va a integrar y no asumir que "el módulo está completo" solo porque el repositorio existe.
- El valor de usar la IA aquí no fue "que escriba el código", sino que **detectara incompatibilidades estructurales antes de generar nada** y las presentara como decisiones explícitas del equipo. El criterio de qué convención usar, qué copiar y qué NO inventar lo definió el equipo en cada una de las tres preguntas planteadas — la IA ejecutó una decisión ya tomada, no la tomó por su cuenta.
- Falta correr la suite de migraciones y tests en un entorno real (Docker/CI del proyecto) antes de mergear esta rama: la revisión de la IA fue estática (sintaxis, nombres, dependencias declaradas), no una prueba de ejecución real.

---

## Entrada — 11 de agosto de 2026
**Tema:** Levantar el entorno local del proyecto (base de datos MySQL + `composer run dev`) y depurar los fallos de arranque encontrados en el proceso
**Participantes:** Equipo de desarrollo ISW-521
**Herramienta consultada:** Claude (Anthropic), vía CLI con acceso a la terminal y al código del proyecto

---

### 1. Qué se le consultó a la IA

Se pidió, en varios pasos sucesivos dentro de la misma sesión:
- "Necesito levantar el proyecto, con composer."
- Orientación paso a paso para crear la base de datos MySQL e importar un dump SQL propio (`sistema_gestion_academica_utn (1).sql`), incluyendo cómo entrar a la consola de `mysql` y si usar MySQL Workbench o la terminal.
- Ayuda para corregir comandos de PowerShell que fallaban al intentar redirigir el dump con `<`.
- Cuáles son las credenciales activas para iniciar sesión en la aplicación.
- Verificar, antes de hacer commit, si el módulo de Solicitudes Estudiantiles seguía activo tras los cambios realizados.

### 2. Qué encontró la IA antes de actuar (y por qué no asumió que era un problema de base de datos)

1. **El primer error (`Invalid route action: [...PermissionComponent]`) no era de base de datos.** Ocurría al registrar rutas, antes de que la app tocara MySQL — se confirmó ejecutando `php artisan route:clear`, que falla igual sin conexión a datos. La IA no aceptó la hipótesis inicial del equipo ("debo levantar la base de datos primero") sin antes verificarla con `class_exists()` contra el autoload puro de Composer, que devolvió `false`: la clase no se estaba autoloadeando. La causa real era un `vendor/composer/autoload_*.php` desactualizado (probablemente por el módulo `Permission` agregado en un commit reciente sin correr `composer dump-autoload` después).
2. **El dump SQL importado no traía la tabla `migrations`.** Se detuvo antes de asumir cualquier cosa y preguntó al equipo cómo proceder (marcar migraciones como aplicadas vs. reconstruir desde cero) en lugar de decidir unilateralmente.
3. **Incompatibilidad de esquema no evidente a simple vista:** tras marcar las migraciones como aplicadas, `migrate:fresh` reveló que el dump importado correspondía al **esquema viejo en español** (`carreras`, `solicitudes`, `reglas_levantamiento`) de una etapa anterior del proyecto, mientras que el código actual (tras la traducción registrada en la entrada del 10 de agosto) usa **tablas en inglés** (`careers`, `requests`, `waiver_rules`). La IA comparó con `diff` los pares de migraciones sospechosas antes de proponer una solución, en vez de borrar archivos a ciegas.
4. **Bug real en el repositorio:** confirmó, migración por migración, que el set `2026_08_03_*` (10 archivos, en español) era un residuo de la migración a inglés del 6-7 de agosto que nunca se eliminó — la causa de que `migrate:fresh` fallara con "table already exists".

### 3. Qué se aceptó de la respuesta de la IA

- El diagnóstico del error de autoload y la corrección con `composer dump-autoload`.
- Crear la base de datos e importar el dump vía consola de `mysql` (no Workbench, por decisión explícita del equipo tras comparar ambas opciones).
- Marcar inicialmente las 30 migraciones como aplicadas sin tocar los datos importados (primera opción elegida por el equipo cuando se le preguntó).
- Al descubrirse la incompatibilidad de esquema, la propuesta de **reconstruir con `migrate:fresh --seed`** usando las migraciones en inglés como fuente de verdad, aceptando la pérdida de los datos del dump importado (que ya no eran compatibles con el código actual).
- La eliminación de los 10 archivos de migración obsoletos en español, tras ver el `diff` que demostraba que eran duplicados exactos (traducidos) de los del 6-7 de agosto.
- Las credenciales de prueba tal como están definidas en `DatabaseSeeder.php` (`prueba@gmail.com` / `admin@gmail.com`, clave `12345678`), sin que la IA las inventara.

### 4. Qué se rechazó y por qué

- **Se rechazó (implícitamente, al pedir verificación) dar por buena la corrección solo por el mensaje de éxito de la terminal.** El equipo pidió explícitamente comprobar, tanto en el navegador como leyendo el código (`resources/views/requests/request/livewire/request-component.blade.php`), que el módulo de Solicitudes Estudiantiles seguía activo antes de confirmar el commit — no bastaba con que `migrate:fresh --seed` terminara sin errores.
- **Se rechazó continuar usando el dump SQL original** una vez confirmado que su esquema (español) no correspondía al código actual (inglés): mantenerlo habría dejado la aplicación con tablas duplicadas o inservibles.

### 5. Qué hubo que corregir o verificar manualmente

- La IA no pudo ejecutar los comandos interactivos de `mysql` que requerían escribir la contraseña (no hay TTY en el entorno de ejecución de comandos) — el equipo tuvo que teclear esos pasos manualmente en su propia terminal, guiado por la IA.
- Se corrigió un error de sintaxis de PowerShell: `<` no redirige entrada hacia un ejecutable externo como en Bash/CMD; hubo que envolver el comando en `cmd /c '...'` para que la importación del dump funcionara.
- Se verificó manualmente, consultando la base de datos con `SHOW TABLES`, que ninguna tabla en inglés (`careers`, `students`, `requests`, etc.) existiera antes de reconstruir — la IA no asumió el diagnóstico sin evidencia directa de la base de datos real.
- Se verificó en el navegador (pantalla `/solicitudes` cargando la tabla, filtros y botón "Agregar") y en el código (existencia y tamaño real del archivo Blade) que el módulo de Solicitudes seguía funcional después de reconstruir el esquema.

### 6. Qué se aprendió del proceso

- Un error de arranque de Laravel no siempre es lo que "parece" (en este caso, todo apuntaba a la base de datos porque fue lo primero que se mencionó) — vale la pena que la IA aísle la causa raíz con evidencia (`class_exists`, `route:clear` sin DB) antes de seguir la hipótesis inicial del equipo.
- Importar un dump SQL crudo a un proyecto Laravel no reconstruye la tabla `migrations`: el equipo debe decidir explícitamente si "finge" que las migraciones ya corrieron o si reconstruye desde el código — no es una decisión que la IA deba tomar sola porque implica un trade-off de datos reales vs. consistencia con el código.
- Los residuos de una migración de idioma (español → inglés) documentada en la entrada anterior del diario no eran solo un detalle cosmético: se convirtieron en un bug funcional real (`migrate:fresh` roto) semanas después, cuando alguien intentó reconstruir la base de datos desde cero. Vale la pena, como equipo, limpiar archivos obsoletos en el mismo commit donde se hace una traducción/renombrado, no dejarlos "por si acaso".
- Verificar un cambio en el código fuente (¿existe la vista?, ¿tiene contenido?) además de en el navegador da una confirmación más completa que solo probar visualmente — especialmente antes de un commit que borra archivos.

---
