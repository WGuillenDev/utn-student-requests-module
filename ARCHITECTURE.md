# Arquitectura hexagonal

Este proyecto es un Laravel 13 + Livewire 4 + Fortify estándar (starter kit oficial `laravel/livewire-starter-kit`), organizado con una capa de dominio separada del framework siguiendo el patrón de puertos y adaptadores (arquitectura hexagonal).

## Capas

```
app/
├── Domain/            Reglas de negocio puras. Sin Laravel. Ver app/Domain/README.md
├── Application/        Casos de uso, orquestan el Domain. Ver app/Application/README.md
├── Infrastructure/      Adaptadores: Eloquent, providers de bindings. Ver app/Infrastructure/README.md
├── Http/Controllers/    Adaptador de entrada HTTP (convención estándar de Laravel, no se mueve)
├── Livewire/            Acciones Livewire puntuales (ej. Logout) del scaffold de autenticación
├── Models/              Solo el modelo User (framework/autenticación vía Fortify)
└── Providers/            Providers propios del scaffold (AppServiceProvider, FortifyServiceProvider)

resources/views/pages/    Componentes Livewire de un solo archivo (adaptador de entrada de UI)
```

## Regla de dependencias

```
Infrastructure  →  Application  →  Domain
     (nada apunta hacia Infrastructure; Domain no apunta hacia nada)
```

- **Domain** no importa nada de `Illuminate\*` ni de ninguna otra capa.
- **Application** solo importa de `Domain` (entidades, value objects, interfaces de repositorio).
- **Infrastructure** importa de `Application` y `Domain` para implementarlos (repositorios Eloquent, controladores, componentes Livewire), nunca al revés.
- Los componentes Livewire (`resources/views/pages/...`) y los controladores HTTP son adaptadores de entrada: reciben la petición, arman un DTO, invocan un caso de uso de `Application` y renderizan el resultado. No deben contener lógica de negocio ni tocar Eloquent directamente.

## Cómo agregar un módulo nuevo

Ejemplo con un hipotético módulo `Solicitudes` (una solicitud estudiantil):

1. `app/Domain/Solicitudes/` — entidad `Solicitud`, value objects, interfaz `SolicitudRepository`.
2. `app/Application/Solicitudes/` — casos de uso (`CrearSolicitud`, `AprobarSolicitud`, `ListarSolicitudes`) + DTOs.
3. `app/Infrastructure/Persistence/Eloquent/Solicitudes/` — `Models/SolicitudModel.php` + `Repositories/EloquentSolicitudRepository.php` (implementa la interfaz del paso 1).
4. Migración en `database/migrations/` como de costumbre (Eloquent sigue siendo el ORM, solo que el modelo vive en `Infrastructure`, no en `app/Models`).
5. Registrar el binding `SolicitudRepository::class => EloquentSolicitudRepository::class` en `app/Infrastructure/Providers/DomainServiceProvider.php`.
6. Adaptador de entrada:
   - Livewire: `resources/views/pages/solicitudes/⚡index.blade.php`, inyecta los casos de uso del paso 2 (no el repositorio ni el modelo directamente).
   - o API: `app/Infrastructure/Http/Controllers/Solicitudes/SolicitudController.php` + ruta en `routes/web.php` o `routes/api.php`.

Cada capa tiene su propio `README.md` con más detalle y un ejemplo completo (no funcional, solo referencial) de este mismo módulo de ejemplo.

## Lo que NO se tocó

El scaffold de autenticación (Fortify, 2FA, passkeys, perfil, apariencia) se dejó tal cual lo genera `laravel/livewire-starter-kit`, en sus ubicaciones estándar de Laravel (`app/Models/User`, `app/Providers/FortifyServiceProvider`, `app/Actions/Fortify`, `resources/views/pages/auth` y `resources/views/pages/settings`). Es infraestructura de autenticación ya resuelta por el starter kit; no forma parte del dominio de negocio que vas a modelar.
