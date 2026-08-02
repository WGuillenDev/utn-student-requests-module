# Domain

Capa de reglas de negocio puras. No depende de Laravel ni de ninguna otra capa.

## Convención por módulo

```
app/Domain/<Modulo>/
├── Entities/         # Entidades / agregados (clases PHP planas, sin Eloquent)
├── ValueObjects/      # Objetos de valor inmutables (Email, Carnet, etc.)
├── Repositories/       # Interfaces de repositorio (puertos de salida)
├── Services/          # Servicios de dominio (lógica que no pertenece a una sola entidad)
├── Events/            # Eventos de dominio
└── Exceptions/        # Excepciones propias del dominio
```

## Reglas

- Cero `use Illuminate\...`. Nada de Eloquent, Facades, Request, etc. aquí.
- Las entidades no conocen la base de datos: no tienen `save()`, `find()`, ni casts de Eloquent.
- Los repositorios se definen aquí como **interfaces** (el "puerto"); la implementación con Eloquent vive en `app/Infrastructure/Persistence`.
- Si una regla de negocio se puede probar sin arrancar Laravel, pertenece a esta capa.

## Ejemplo (referencial, no implementado)

```php
// app/Domain/Estudiantes/Entities/Estudiante.php
namespace App\Domain\Estudiantes\Entities;

final class Estudiante
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $carnet,
        public readonly string $nombre,
        public readonly bool $activo,
    ) {}
}

// app/Domain/Estudiantes/Repositories/EstudianteRepository.php
namespace App\Domain\Estudiantes\Repositories;

use App\Domain\Estudiantes\Entities\Estudiante;

interface EstudianteRepository
{
    public function findById(int $id): ?Estudiante;
    public function save(Estudiante $estudiante): void;
}
```
