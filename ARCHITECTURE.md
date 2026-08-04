# Hexagonal Architecture

This project is a standard Laravel 13 + Livewire 4 + Fortify app (official `laravel/livewire-starter-kit`), organized with a domain layer decoupled from the framework, following the Ports & Adapters (hexagonal architecture) pattern.

## Layers

```
app/
├── Domain/            Pure business rules. No Laravel. See app/Domain/README.md
├── Application/        Use cases, orchestrate the Domain. See app/Application/README.md
├── Infrastructure/      Adapters: Eloquent, binding providers. See app/Infrastructure/README.md
├── Http/Controllers/    HTTP entry adapter (standard Laravel convention, not moved)
├── Livewire/            One-off Livewire actions (e.g. Logout) from the auth scaffold
├── Models/              Only the User model (framework/authentication via Fortify)
└── Providers/            Scaffold-owned providers (AppServiceProvider, FortifyServiceProvider)

resources/views/pages/    Single-file Livewire components (UI entry adapter)
```

## Dependency rule

```
Infrastructure  →  Application  →  Domain
     (nothing points to Infrastructure; Domain points to nothing)
```

- **Domain** imports nothing from `Illuminate\*` or any other layer.
- **Application** only imports from `Domain` (entities, value objects, repository interfaces).
- **Infrastructure** imports from `Application` and `Domain` to implement them (Eloquent repositories, controllers, Livewire components), never the other way around.
- Livewire components (`resources/views/pages/...`) and HTTP controllers are entry adapters: they receive the request, build a DTO, invoke an `Application` use case, and render the result. They must never contain business logic or touch Eloquent directly.

## How to add a new module

Example with a hypothetical `Requests` module (a student request):

1. `app/Domain/Requests/` — `Request` entity, value objects, `RequestRepository` interface.
2. `app/Application/Requests/` — use cases (`CreateRequest`, `ApproveRequest`, `ListRequests`) + DTOs.
3. `app/Infrastructure/Persistence/Eloquent/Requests/` — `Models/RequestModel.php` + `Repositories/EloquentRequestRepository.php` (implements the step-1 interface).
4. Migration under `database/migrations/` as usual (Eloquent is still the ORM — the model just lives in `Infrastructure`, not `app/Models`).
5. Register the binding `RequestRepository::class => EloquentRequestRepository::class` in `app/Infrastructure/Providers/DomainServiceProvider.php`.
6. Entry adapter:
   - Livewire: `resources/views/pages/requests/⚡index.blade.php`, injects the step-2 use cases (never the repository or model directly).
   - or API: `app/Infrastructure/Http/Controllers/Requests/RequestController.php` + a route in `routes/web.php` or `routes/api.php`.

Each layer has its own `README.md` with more detail and a full (non-functional, referential-only) example for this same sample module.

## What was NOT touched

The authentication scaffold (Fortify, 2FA, passkeys, profile, appearance) was left exactly as `laravel/livewire-starter-kit` generates it, in its standard Laravel locations (`app/Models/User`, `app/Providers/FortifyServiceProvider`, `app/Actions/Fortify`, `resources/views/pages/auth`, and `resources/views/pages/settings`). It's authentication infrastructure already solved by the starter kit — it isn't part of the business domain you're modeling.
