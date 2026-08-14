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

## Entrada — 13 de agosto de 2026
**Tema:** Definición y creación de 3 perfiles de prueba (Superadmin, Estudiante, Coordinadora de Docencia) para validar el módulo de Solicitudes Estudiantiles
**Participantes:** Equipo de desarrollo ISW-521
**Herramienta consultada:** Claude (Anthropic), vía chat para el análisis/diseño y vía Claude Code en terminal para la implementación

---

### 1. Qué se le consultó a la IA

En una primera sesión (chat), se le pidió analizar el repositorio base del docente (`SIGA-UTN`) y compararlo contra el proyecto propio para saber si ya traía usuarios/roles de prueba reutilizables, y luego proponer qué perfiles de prueba crear para poder ver la aplicación "como estudiante" y "como docente/revisor", basándose en:
- El documento oficial del proyecto (`Proyecto_4_Solicitudes_Estudiantiles (3).docx`).
- El script SQL oficial del sistema completo (`sistema_gestion_academica_utn (1).sql`), en particular su tabla `roles`.

Después de acordar los 3 perfiles y sus permisos, se le pidió a la IA un prompt autocontenido para ejecutar la implementación en una segunda sesión (Claude Code en terminal), y finalmente que revisara el resultado antes del commit.

### 2. Qué encontró la IA antes de actuar (y por qué no aceptó la primera idea del equipo tal cual)

1. **El repo del docente no aporta roles académicos.** Se verificó archivo por archivo (`RoleSeeder.php`, `PermissionSeeder.php`, migraciones) que el proyecto del docente solo siembra `Superadmin`/`Admin` — sin `Estudiante` ni ningún rol de negocio. La idea inicial de "asimilar perfiles ya existentes en la BD del docente" no tenía nada que asimilar; había que crearlos desde cero.
2. **La propuesta original del equipo era "Estudiante, Docente, Superadmin".** La IA señaló que la palabra "Docente" no aparece ni una sola vez en el documento oficial del proyecto como actor del sistema, y que en el catálogo oficial de roles (`sistema_gestion_academica_utn (1).sql`) el rol `Docente` existe pero representa a un profesor que consulta su oferta de cursos (`oferta.consultar`, `archivos.descargar`) — no tiene ningún permiso relacionado con solicitudes. Usar ese rol para "ver la bandeja de revisión" habría dado un usuario de prueba que ni siquiera puede entrar al módulo. El rol correcto, según el propio SRS (ES-01 y ES-04) y la columna `solicitudes.revisor_id` del esquema oficial (comentario literal: *"Usuario revisor (Docencia/Comisión)"*), es `Coordinadora de Docencia`.
3. **`Superadmin` no sirve para validar criterios de aceptación.** La IA explicó que `Superadmin` tiene un `Gate::before` que bypasea toda autorización — usarlo como perfil de prueba principal ocultaría cualquier bug real de permisos/scoping, en vez de confirmar que el módulo funciona para un usuario con permisos reales y limitados.
4. **Gap real detectado en el código, no solo de nomenclatura:** `RequestPolicy::view()`/`viewAny()` no filtran por dueño de la solicitud. La IA lo marcó explícitamente antes de crear el rol `Estudiante`: con los permisos propuestos, un estudiante vería las solicitudes de *todos* los estudiantes, lo cual contradice el criterio de aceptación literal de ES-03 ("el estudiante debe poder consultar el estado de **sus** solicitudes"). Se decidió, como equipo, crear los perfiles primero y dejar ese fix como tarea aparte explícita, en vez de que la IA lo resolviera sin que se le pidiera.

### 3. Qué se aceptó de la respuesta de la IA

- El mapeo final de roles: `Estudiante` → rol `Estudiante` (permisos `requests.create`, `requests.view`); `Docencia` → rol `Coordinadora de Docencia` (permisos `requests.view/search/review/export_pdf/export_excel` + `waiver_rules.create/view/edit/delete`); `Superadmin` se mantiene como está.
- Las credenciales de ejemplo propuestas (`estudiante@gmail.com` / `docencia@gmail.com`, clave `12345678`), siguiendo el mismo patrón que los usuarios ya existentes en `DatabaseSeeder.php`.
- Vincular el usuario `Estudiante` a un registro real de `StudentModel` (vía `user_id`), para que exista un expediente contra el cual el módulo pueda operar.
- El prompt autocontenido generado para ejecutar el cambio en una sesión de Claude Code aparte, delegando la escritura del código (decisión explícita del equipo, no una desviación no autorizada).
- El diagnóstico de que no hacía falta ninguna migración nueva: las columnas necesarias (`roles.name`, `students.user_id`, `requests.user_id`) ya existían en el esquema.

### 4. Qué se rechazó y por qué

- **Se rechazó el nombre "Docente" para el rol revisor**, por las razones del punto 2: no es un actor del SRS y, en el propio catálogo oficial, ese nombre está reservado para un perfil sin relación con solicitudes.
- **Se rechazó usar `Superadmin` como perfil principal de prueba funcional**, por el mismo motivo del punto 3 del apartado anterior.
- **Se rechazó (por ahora) que la IA implementara el fix de scoping por dueño en `RequestPolicy`** junto con la creación de los perfiles, para no mezclar dos cambios de alcance distinto en el mismo commit — queda como tarea explícita pendiente, documentada aquí.
- **Se rechazó el código entregado por la sesión de Claude Code en su primera versión**, ver punto 5.

### 5. Qué hubo que corregir o verificar manualmente — el error real de la IA

Al revisar el `diff` generado por la sesión de Claude Code que implementó los seeders, se encontró que **la IA no respetó la convención de nombres ya establecida en el proyecto** (identificadores de código en inglés, valores de datos en español): las variables nuevas quedaron en español (`$estudiante`, `$docencia`, `$estudianteRole`, `$estudianteUser`, `$docenciaRole`, `$docenciaUser`) en `RoleSeeder.php` y `DatabaseSeeder.php`, a pesar de que el propio código que la IA tenía como contexto (y que ella misma tradujo en la entrada del 10 de agosto) usa inglés de forma consistente en todo lo demás.

Esto es un ejemplo concreto de error de la IA: tuvo el contexto correcto disponible (el resto del archivo, escrito en inglés) y aun así generó variables nuevas en español, probablemente porque copió el idioma de los *valores* de rol (`'Estudiante'`, `'Coordinadora de Docencia'`) hacia los *nombres de variable*, sin distinguir que esa regla del proyecto aplica solo al contenido de datos, no a los identificadores. Se corrigió con un segundo prompt específico, dando la lista exacta de renombres (`$estudiante` → `$studentRole`, `$docencia` → `$teachingCoordinatorRole`, etc.) sin tocar ningún string literal ni la lógica.

Después de la corrección, se verificó:
- Con `git diff --stat` que solo se modificaron los 2 archivos esperados.
- Contra la base de datos real (vía `php artisan tinker`) que los 4 roles (`Superadmin`, `Admin`, `Estudiante`, `Coordinadora de Docencia`) y sus permisos quedaron sincronizados exactamente como se pidió, que los 4 usuarios existen con el rol correcto, y que el `StudentModel` del usuario Estudiante quedó vinculado (`user_id` correcto).
- Con `php artisan test` que la suite sigue en el mismo estado que antes del cambio (32/33, con la única falla preexistente y no relacionada de `AuthenticationTest::test_login_screen_can_be_rendered`).

### 6. Qué se aprendió del proceso

- Un nombre de rol "parece correcto" a simple vista si suena razonable (`Docente` para quien revisa solicitudes de docencia), pero solo verificarlo contra el documento oficial del SRS y el esquema de base de datos reveló que era el rol equivocado. Vale la pena, como equipo, contrastar cualquier nombre de rol/actor contra la fuente oficial antes de codificarlo, en vez de asumir por el nombre que suena bien.
- Un perfil con bypass total (`Superadmin`) es útil para administración, pero inútil (y hasta contraproducente) para validar que las reglas de autorización de un módulo funcionan de verdad — probar "como superadmin" puede dar una falsa sensación de que todo funciona.
- La convención "código en inglés, datos en español" no es intuitiva para una IA si el prompt solo le da los nombres de rol en español: hay que ser explícito en el prompt sobre qué parte del código debe traducirse y cuál no, en vez de asumir que la IA va a inferir la regla del contexto general del archivo. Esto quedó confirmado como un error real y verificable (no hipotético) de la IA en este proyecto.
- Delegar la implementación a una sesión de terminal aparte (con un prompt autocontenido y explícito) y luego revisar el `diff` como paso separado permitió detectar este error antes del commit — revisar el resultado, no solo confiar en que "corrió sin errores", sigue siendo responsabilidad del equipo.
- Queda pendiente, documentado explícitamente para no perderlo: el scoping por dueño en `RequestPolicy` (para que `Estudiante` solo vea sus propias solicitudes) y una vista dedicada "Mis Solicitudes" — ninguno de los dos se implementó en este ciclo a propósito, para mantener el commit acotado a la creación de perfiles.

---

## Entrada — 14 de agosto de 2026
**Tema:** Diagnóstico y corrección de un bug de legibilidad en los `<select>` del portal de estudiante en modo oscuro
**Participantes:** Equipo de desarrollo ISW-521
**Herramienta consultada:** Claude (Anthropic), vía Claude Code en terminal, con acceso al navegador (Chrome) para reproducir el bug visualmente

---

### 1. Qué se le consultó a la IA

Se reportó, de forma informal, un bug observado manualmente en el portal de estudiante (`/mis-solicitudes`) con el tema oscuro activado: al abrir cualquiera de los `<select>` del formulario de nueva solicitud ("Curso a matricular", "Requisito no cumplido", "Curso interno equivalente"), la lista de opciones se veía casi en blanco sobre blanco, ilegible. Se le pidió a la IA que reprodujera el bug y propusiera cómo solucionarlo.

### 2. Qué encontró la IA antes de actuar — incluyendo un diagnóstico inicial incorrecto

1. La IA reprodujo el bug en un navegador real controlado por Claude Code (login como Estudiante, abrir el formulario, abrir el `<select>`, capturar pantalla) antes de tocar una sola línea de código.
2. **Primer diagnóstico (incorrecto):** revisando `resources/css/app.css`, notó que el bloque `.form-field` estiliza `label`, `input[type="text"]`, `textarea` e `input[type="file"]`, pero no tiene ninguna regla para `select`. Concluyó que la causa era la ausencia de la propiedad CSS `color-scheme` en `:root`/`[data-theme="dark"]`, que le indica al navegador si debe dibujar los controles nativos (incluida la lista desplegable de un `<select>`) en variante clara u oscura. Propuso agregar `color-scheme: light` / `color-scheme: dark` a esos dos bloques.
3. Con autorización explícita del equipo ("sí hazlo tú"), aplicó ese cambio y volvió a probar en el navegador. **El bug seguía exactamente igual** — el desplegable seguía blanco sobre blanco.
4. En vez de dar el cambio por bueno solo porque "sonaba correcto", la IA verificó con JavaScript en la página real (`getComputedStyle`) que `color-scheme: dark` sí se estaba aplicando correctamente al `<select>` (confirmado: `colorScheme: "dark"`), lo que descartó su propia primera hipótesis — el problema no era que faltara `color-scheme`.
5. Comparó el `<select>` roto contra otro que sí funciona en el resto del sistema (`.control-group select`, usado en los filtros de las pantallas CRUD) y encontró la diferencia real: `.control-group select` sí define `background: var(--cardBg)` explícito, mientras que `.form-field select` no define ningún fondo y queda transparente. Con fondo transparente, Chromium no pinta nada detrás de las opciones del desplegable nativo, así que se ve el blanco por defecto del sistema operativo con el texto claro (heredado de `--textPrimary` en modo oscuro) casi invisible encima.

### 3. Qué se aceptó de la respuesta de la IA

- El diagnóstico final (fondo transparente en `.form-field select`, no ausencia de `color-scheme`), una vez verificado en el navegador con capturas de pantalla y `getComputedStyle`.
- La corrección aplicada: agregar `select` a la regla existente `.form-field input[type="text"], .form-field textarea` (mismo `background: var(--cardBg)`, `color: var(--textPrimary)`) y una regla explícita `.form-field select option { background: var(--cardBg); color: var(--textPrimary); }`.
- Mantener también el cambio de `color-scheme` (aunque no fue la causa raíz), por ser buena práctica general para otros controles nativos (scrollbars, checkboxes) que si dependen de esa propiedad.
- La verificación visual final: se probaron los tres `<select>` del portal (levantamiento y convalidación) en modo oscuro, con capturas de pantalla mostrando texto blanco legible sobre fondo azul oscuro en cada opción.

### 4. Qué se rechazó y por qué

- Se rechazó dar por cerrado el bug después del primer cambio (`color-scheme`) solo porque era la explicación más "de manual" — el equipo esperó a que la IA repitiera la prueba visual en el navegador antes de aceptar la corrección, y esa misma prueba fue la que reveló que el primer intento no había funcionado.

### 5. Qué hubo que corregir o verificar manualmente — el error real de la IA

Este es el caso concreto de error de la IA para esta entrada: **el primer diagnóstico y su corrección (`color-scheme`) fueron incorrectos.** La IA razonó por analogía con la causa más conocida/documentada de este tipo de bug (falta de `color-scheme` para forzar controles nativos oscuros) sin antes comparar el `<select>` roto contra un `<select>` que sí funciona dentro del mismo proyecto. Ese paso de comparación —que hubiera sido más rápido y habría llevado directo a la causa real— se hizo únicamente *después* de que la primera corrección fallara la prueba visual, no antes.

Se verificó, en ambos intentos, con:
- Capturas de pantalla (`zoom`) del `<select>` abierto, antes y después de cada cambio.
- `getComputedStyle` ejecutado en vivo sobre la página para confirmar qué propiedades CSS se estaban aplicando realmente (`color-scheme`, `color`, `background-color`) en el `<select>` y en sus `<option>`, en lugar de asumir el resultado a partir del CSS fuente.

### 6. Qué se aprendió del proceso

- Un diagnóstico "razonable" o "de manual" (`color-scheme` para dark mode en controles nativos) no reemplaza la verificación empírica: la única forma de confirmar que una corrección de CSS realmente funciona es probarla visualmente en el navegador contra el bug original, no solo revisar que el código "se ve correcto".
- Comparar el elemento roto contra un elemento equivalente que sí funciona en el mismo proyecto (`.control-group select` vs. `.form-field select`) fue más eficaz para encontrar la causa raíz que razonar desde documentación general sobre CSS y navegadores — vale la pena aplicar esa comparación como primer paso la próxima vez, antes de proponer una corrección basada en teoría.
- `color-scheme` en CSS afecta la apariencia por defecto de controles nativos, pero no sustituye un `background-color` explícito cuando el elemento (o sus ancestros) deja el fondo transparente: en Chromium, un `<select>` con fondo transparente no hereda un fondo oscuro solo por `color-scheme: dark`, porque el navegador sigue mostrando su lienzo nativo (blanco) detrás del texto heredado.
- Volver a probar en el navegador después de cada cambio, en vez de encadenar varias correcciones antes de verificar, permitió aislar rápidamente que el primer cambio no había sido el culpable real.

---

## Entrada — 14 de agosto de 2026 (continuación)
**Tema:** Validación en tiempo real de archivos adjuntos, subida por arrastrar-y-soltar (drag-and-drop) y botón para quitar un archivo mal adjuntado, en el portal de estudiante
**Participantes:** Equipo de desarrollo ISW-521
**Herramienta consultada:** Claude (Anthropic), vía Claude Code en terminal, con acceso al navegador (Chrome) para reproducir y verificar cada cambio

---

### 1. Qué se le consultó a la IA

En la misma sesión de trabajo, se reportaron dos bugs adicionales observados manualmente en `/mis-solicitudes`:
1. Al adjuntar un archivo superior a 5MB en "Nueva solicitud de levantamiento" o "Nueva solicitud de convalidación", el sistema lo deja seleccionar sin avisar nada; el error debería aparecer justo debajo del botón "Seleccionar archivo".
2. En la solicitud de convalidación (3 documentos obligatorios), al enviar sin archivos aparecen correctamente los 3 errores "obligatorio"; pero al adjuntar los archivos uno por uno para corregirlo, el error rojo del campo ya adjuntado se queda ahí en vez de quitarse.

Se le pidió a la IA que primero probara ambos bugs en el navegador para confirmar el problema antes de proponer nada. Después, ya con ambos corregidos, se le consultó qué se podía hacer para agregar arrastrar-y-soltar (drag-and-drop) "como en los sistemas modernos", y luego un botón pequeño de "×" para quitar un archivo mal adjuntado sin tener que recargar la página.

### 2. Qué encontró la IA antes de actuar

1. Reprodujo ambos bugs en el navegador antes de tocar código: subió un PDF de 6MB sintético (generado con PowerShell) al campo de levantamiento y confirmó que no aparecía ningún error hasta hacer clic en "Enviar solicitud" — el backend sí rechazaba el archivo (`max:5120` ya existía en `WaiverRequestForm`/`ValidationRequestForm`), pero solo al final del flujo, no al seleccionar el archivo. Luego reprodujo el segundo bug en convalidación: adjuntó un PDF válido en "Programa del curso externo" después de un envío fallido y confirmó que el mensaje "es obligatorio" seguía visible pese a que el archivo ya estaba adjunto.
2. Diagnosticó que ambos bugs comparten la misma causa: Livewire no revalida un campo de archivo cuando el usuario lo cambia, solo cuando se llama `validate()` en el envío completo del formulario. La corrección fue un hook `updated(string $property)` en `StudentRequestComponent` que llama a `$this->validateOnly($property)` en cuanto cambia cualquiera de los 4 campos de archivo — Livewire detecta automáticamente que la propiedad pertenece a un Form Object y delega la validación a las reglas ya existentes ahí, sin duplicarlas.
3. Verificó, leyendo el código fuente de Livewire (`HandlesValidation::validateOnly`), que al validar un campo con éxito el framework limpia automáticamente (`resetErrorBag`) solo el error de ese campo específico — confirmando, antes de escribir nada, que este único hook resolvía los dos bugs a la vez (error inmediato + limpieza automática del error viejo).
4. Para el drag-and-drop, explicó la opción elegida (envolver el `<input type="file">` existente en una zona con Alpine.js, ya incluido vía Livewire) frente a la alternativa de usar la API `$wire.upload()` de Livewire directamente, señalando que esta última duplicaría la validación que ya vive en los Form Objects.
5. Para el botón de quitar archivo, identificó que un `<input type="file">` nativo no se puede "limpiar" con una X propia por CSS/JS simple — la solución es ocultar el input y mostrar una "pastilla" (nombre + botón ×) cuando el archivo es válido, y que el botón llame a un método Livewire que ponga la propiedad en `null`.

### 3. Qué se aceptó de la respuesta de la IA

- El hook `updated()` con `validateOnly()` para las 4 validaciones en tiempo real, y el mensaje verde "Archivo adjuntado" (`.form-success`) cuando el campo queda válido.
- El patrón de drag-and-drop reutilizando el mismo `<input>` y `wire:model` ya existentes (vía `x-ref` + `DataTransfer` + `dispatchEvent(new Event('change'))`), en vez de un uploader paralelo.
- El método genérico `removeFile(string $field)` (con una lista blanca `FILE_FIELDS`, la misma ya usada por el hook `updated()`) en vez de 4 métodos repetidos, uno por campo.
- El patrón de "pastilla" (`.file-chip`) con el nombre del archivo y un botón × reutilizando los colores de "eliminar" (`--actionDeleteBg`/`--actionDeleteText`) ya existentes en el sistema, en vez de introducir una paleta nueva.

### 4. Qué se rechazó y por qué

- Se rechazó (implícitamente, al no pedirlo) usar `$wire.upload()` para el drag-and-drop: habría significado mantener dos caminos de subida y validación en paralelo para el mismo campo.
- Se rechazó dejar el hook `updated()` genérico sin una lista blanca de campos: la IA lo acotó a los 4 campos de archivo conocidos (`FILE_FIELDS`) en vez de revalidar cualquier propiedad que cambie, para no disparar validaciones innecesarias en otros campos del formulario (curso, institución, etc.) que no las necesitan.

### 5. Qué hubo que corregir o verificar manualmente — el error real de la IA

Después de implementar el drag-and-drop, el equipo notó un efecto visual roto: el texto nativo del navegador ("Ningún archivo seleccionado") aparecía cortado con puntos suspensivos ("Ningún archi...eleccionado"). Este es un bug que **la propia IA introdujo** al envolver el `<input type="file">` en el nuevo `<div class="dropzone">`.

La causa: `.form-field` es `display:flex; flex-direction:column`, y antes del cambio el `<input>` era hijo *directo* de ese contenedor, por lo que el `align-items: stretch` por defecto de flexbox lo estiraba al ancho completo (~520px), dándole al navegador espacio de sobra para mostrar el texto completo. Al envolverlo en `.dropzone` (un `div` normal, no flex), el input dejó de ser hijo directo del flex y volvió a su ancho nativo/intrínseco, mucho más angosto — y Chrome trunca con "..." el texto de su propio control nativo cuando no le entra. La IA no anticipó este efecto colateral de layout al proponer el wrapper; lo detectó el equipo visualmente, no la IA de forma proactiva. Se corrigió agregando `.dropzone input[type="file"] { display:block; width:100%; }` para restaurar el mismo ancho de antes.

También se verificó, dado que las herramientas de automatización de navegador no pueden simular un arrastre real de archivos desde el sistema operativo, el flujo de drop completo mediante un archivo sintético (`new File(...)` + `DataTransfer` + evento `drop` disparado por JavaScript) en ambas pestañas y en el botón de quitar — confirmando visualmente en cada caso el estado esperado (pastilla verde, error limpiado, vuelta a la zona de arrastre tras quitar).

### 6. Qué se aprendió del proceso

- Un solo hook de validación en tiempo real (`updated()` + `validateOnly()`) resolvió dos bugs reportados por separado porque compartían la misma causa raíz — vale la pena, antes de corregir síntomas por separado, confirmar si comparten un origen común.
- Envolver un elemento nativo (`<input type="file">`) en un contenedor nuevo puede romper silenciosamente el layout que dependía de que ese elemento fuera hijo *directo* de un contenedor flex — este tipo de regresión no se detecta leyendo el código, solo probando visualmente después de cada cambio de estructura HTML, como ya se había aprendido en la entrada anterior del mismo día.
- Cuando una herramienta de prueba no puede replicar una interacción real del usuario (arrastrar un archivo del sistema operativo), simular el evento del navegador con datos sintéticos (`DataTransfer`, eventos `drop`/`change` disparados por JS) es una alternativa válida para verificar la lógica de la aplicación, siempre que quede documentado que no prueba la interacción de bajo nivel del sistema operativo, solo la reacción de la aplicación al evento.

---

## Entrada — 14 de agosto de 2026 (motor de reglas y fecha estimada)
**Tema:** Conexión del motor de reglas de ES-01 (waiver engine), detección de duplicados, y la fecha estimada de resolución de ES-03 (entrada manual + asignación automática tras 24h)
**Participantes:** Equipo de desarrollo ISW-521
**Herramienta consultada:** Claude (Anthropic), vía Claude Code en terminal, con acceso al navegador para probar en vivo con los 3 perfiles (Estudiante, Docencia, Superadmin)

---

### 1. Qué se le consultó a la IA

El docente pidió que todos los CRUDs del proyecto estuvieran listos para una revisión al día siguiente (15 de agosto). Se le pidió a la IA verificar si el CRUD del perfil Estudiante estaba completo. Al confirmar que faltaba el motor de reglas de ES-01 (ver entrada de contexto más abajo), se le pidió conectarlo, con una aclaración explícita de negocio: **toda solicitud enviada debe quedar en estado "Pendiente" hasta que Docencia la revise y decida** — no debía auto-resolverse aunque el motor concluyera un resultado. Después se le pidió resolver también la fecha estimada de resolución de ES-03 (entrada manual del revisor + asignación automática tras 24h), dejando explícitamente fuera del alcance de esta sesión la notificación por correo electrónico (esa se dejó documentada para un tercer avance posterior del curso, no para la entrega de mañana).

### 2. Qué encontró la IA antes de actuar

1. Antes de tocar código, releyó el documento oficial del proyecto completo (extraído manualmente del `.docx` con `unzip` + `sed`, ya que no hay Python instalado en este entorno) para confirmar los criterios de aceptación exactos de ES-01, ES-03 y ES-04, en vez de trabajar de memoria.
2. Verificó, probando en el navegador como Estudiante, que el motor de reglas no evaluaba nada: toda solicitud quedaba "Pendiente de revisión" sin importar las reglas configuradas — confirmado además por un comentario explícito ya existente en `CreateRequestUseCase.php` ("Running the waiver/validation engine... is intentionally NOT done here").
3. Encontró que la infraestructura de datos para el motor ya existía sin usarse: la entidad `Request` ya tenía los campos `engineResult`/`violatedRuleId`, y el repositorio ya los persistía — solo faltaba la lógica de evaluación y quién la llamara.
4. Ante la ambigüedad del spec sobre si el motor debía cerrar la solicitud automáticamente o no, la IA no asumió una respuesta: preguntó directamente al equipo. Al recibir la regla de negocio (todo queda Pendiente hasta que Docencia decida), señaló que esa decisión, además, concilia mejor ES-01 con ES-04 (que exige que **toda** solicitud, no solo las no concluyentes, aparezca en la bandeja de Docencia) — algo que no era obvio a primera lectura de ES-01 en aislamiento.
5. Para la fecha estimada, revisó `routes/console.php` (sin comandos programados) y el modal de revisión existente (sin campo de fecha), confirmando que ninguna de las dos partes del criterio de ES-03 estaba implementada.

### 3. Qué se aceptó de la respuesta de la IA

- El diseño del motor (`WaiverEngine` como Domain Service puro + `StudentAcademicProfileRepositoryInterface` como puerto + adaptador Eloquent), con la decisión documentada de que la primera regla *activa* en orden es la autoritativa (evita ambigüedad sobre qué pasa con reglas subsecuentes).
- La detección de duplicados antes de correr el motor, con el mensaje exacto del spec ("Este levantamiento ya fue procesado previamente").
- Que `status` nunca se autorresuelva — el `engineResult` queda como sugerencia visible para Docencia, no como una acción automática.
- Aplicar la asignación automática de fecha estimada de forma perezosa (al leer una solicitud), no vía un job programado — así funciona de forma confiable en la demo sin depender de que un scheduler esté corriendo.
- Reutilizar el modal de revisión ya existente para que el revisor ingrese la fecha, en vez de construir una pantalla nueva.

### 4. Qué se rechazó y por qué

- **Se rechazó implementar la notificación por correo de ES-03 en esta sesión.** El equipo aclaró explícitamente que esa funcionalidad corresponde a un tercer avance posterior del curso, no a la entrega de mañana — aunque la IA ya había investigado el gap (confirmó que no existe ningún `Mail::`/`Notification::`/`Mailable` en el proyecto) y lo tenía listo para proponer, se frenó la implementación a pedido del equipo.
- Se rechazó que la IA decidiera por su cuenta el comportamiento de auto-resolución del motor — se le pidió la regla de negocio explícitamente en vez de dejarla inferir del spec.

### 5. Qué hubo que corregir o verificar manualmente — el error real de la IA

Al escribir por primera vez las fixtures de prueba del motor en `TestDataSeeder.php` (curso + reglas + expediente académico del estudiante demo), la IA asumió que ese seeder corría **después** de que `DatabaseSeeder` creara el usuario `estudiante@gmail.com`. En realidad corría **antes**: `TestDataSeeder` estaba en el bloque `$this->call([...])` inicial, y los 4 usuarios de prueba (incluido `estudiante@gmail.com`) se creaban más abajo, fuera de ese bloque. El código tenía un guard silencioso (`if ($demoUser === null) { return; }`) que hizo que las fixtures del motor se saltaran sin ningún error visible en la consola — `migrate:fresh --seed` terminó con "DONE" en todos los seeders, dando la falsa impresión de que todo se había sembrado correctamente.

El error se detectó únicamente porque, en vez de confiar en la salida de la consola, se verificó directamente contra la base de datos (`DB::table('waiver_rules')->count()` devolvió `0` cuando debía ser `2`). Se corrigió moviendo la llamada a `TestDataSeeder` al final de `DatabaseSeeder::run()`, después de crear los 4 usuarios.

También se detectó, verificando el código existente antes de dar por completa la nueva funcionalidad, que `EloquentRequestRepository::save()` nunca escribía la columna `estimated_resolution_date` — un bug preexistente (no introducido en esta sesión) que habría hecho que cualquier fecha ingresada por el revisor se perdiera silenciosamente al guardar. Se corrigió como parte del mismo cambio.

Se verificó todo con datos reales, no solo lectura de código:
- Los 3 resultados del motor (Aprobado/Denegado/Revisión manual), probados en vivo como Estudiante contra un expediente académico sembrado a propósito con notas específicas.
- El flujo de duplicados de punta a punta: aprobar una solicitud como Docencia, reintentar el mismo levantamiento como Estudiante, y confirmar tanto el mensaje en pantalla como que no se creó una fila nueva en `requests`.
- El cálculo de "5 días hábiles" contra un calendario real (jueves 13 de agosto + 5 días hábiles, saltando el fin de semana, da jueves 20 de agosto) — no se asumió que la lógica de fechas estuviera bien solo porque compilaba.

### 6. Qué se aprendió del proceso

- Un comentario de diseño ya escrito en el código ("intentionally NOT done here") es más confiable que inferir del nombre de una clase si algo está implementado — vale la pena leerlos antes de dar por completo un requerimiento.
- Un guard silencioso en un seeder (`if (...) return;`) es peligroso si el resultado no se verifica contra la base de datos real: la consola puede reportar éxito aunque la lógica interna nunca haya corrido.
- Frente a una regla de negocio ambigua en el spec (¿el motor cierra la solicitud o no?), preguntar directamente al equipo evitó construir algo que hubiera que rehacer — y en este caso la respuesta del equipo terminó siendo la que mejor concilia dos requerimientos (ES-01 y ES-04) que a primera vista parecían apuntar en direcciones distintas.
- Aplicar una corrección de forma perezosa (al leer, no por un job programado) es una estrategia más segura para una demo en vivo cuando no se puede garantizar que un proceso en segundo plano esté corriendo.
- Documentar explícitamente qué se deja fuera de una sesión a propósito (como la notificación por correo, pospuesta a un tercer avance) es tan importante como documentar qué se hizo — evita que en una sesión futura, o en la defensa oral, se confunda una decisión de alcance con un olvido.

---
