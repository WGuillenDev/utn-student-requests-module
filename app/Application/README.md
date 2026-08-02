# Application

Orquesta los casos de uso del sistema. Depende de `Domain`, nunca al revés. No sabe si quien la invoca es un componente Livewire, un controlador API o un comando de consola.

## Convención por módulo

```
app/Application/<Modulo>/
├── UseCases/    # Una clase por caso de uso (ej: CrearEstudiante, ListarEstudiantes)
└── DTOs/         # Objetos de transferencia de datos de entrada/salida de los casos de uso
```

## Reglas

- Un caso de uso = una acción del negocio, con un único método público (`handle()` o `__invoke()`).
- Recibe y devuelve DTOs propios de esta capa, nunca modelos Eloquent ni Request de Laravel.
- Depende de las **interfaces** de repositorio definidas en `Domain`, resueltas por inyección de dependencias (el binding concreto se registra en `app/Infrastructure/Providers/DomainServiceProvider.php`).

## Ejemplo (referencial, no implementado)

```php
// app/Application/Estudiantes/UseCases/CrearEstudiante.php
namespace App\Application\Estudiantes\UseCases;

use App\Domain\Estudiantes\Entities\Estudiante;
use App\Domain\Estudiantes\Repositories\EstudianteRepository;

final class CrearEstudiante
{
    public function __construct(
        private readonly EstudianteRepository $estudiantes,
    ) {}

    public function handle(CrearEstudianteDTO $dto): Estudiante
    {
        $estudiante = new Estudiante(null, $dto->carnet, $dto->nombre, activo: true);

        $this->estudiantes->save($estudiante);

        return $estudiante;
    }
}
```

El adaptador de entrada (controlador o componente Livewire) construye el DTO a partir del `Request`/formulario y llama al caso de uso; nunca contiene lógica de negocio.
