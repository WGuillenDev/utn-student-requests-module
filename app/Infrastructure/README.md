# Infrastructure

Los adaptadores: todo lo que conecta el dominio/aplicación con el mundo exterior (base de datos, HTTP, UI, servicios externos). Es la única capa que puede depender de Laravel.

## Estructura

```
app/Infrastructure/
├── Persistence/
│   └── Eloquent/<Modulo>/
│       ├── Models/        # Modelos Eloquent (detalle de infraestructura, no el dominio)
│       └── Repositories/   # Implementan las interfaces de app/Domain/<Modulo>/Repositories
├── Http/
│   └── Controllers/<Modulo>/   # Controladores, si el módulo expone una API
└── Providers/
    └── DomainServiceProvider.php   # Bindings interfaz -> implementación
```

## Adaptadores que ya existen en el proyecto (no se mueven)

- `app/Http/Controllers` y `resources/views/pages/**` (componentes Livewire de un solo archivo) son también adaptadores de entrada — así los usa Laravel/Livewire por convención y así deben quedarse. Los del scaffold de autenticación (Fortify, login, 2FA, passkeys, perfil) no se tocan.
- `app/Models/User.php` es infraestructura ligada a la autenticación (Fortify, `config/auth.php`, `PasskeyAuthenticatable`, etc.) y se queda donde está.

## Convención para módulos de negocio nuevos

Los modelos Eloquent y repositorios concretos de **tus** módulos (los que tú vas a crear) van bajo `Persistence/Eloquent/<Modulo>`, no en `app/Models`. El repositorio Eloquent traduce entre el modelo Eloquent y la entidad de dominio; el resto de la app nunca ve el modelo Eloquent directamente, solo la entidad.

## Ejemplo (referencial, no implementado)

```php
// app/Infrastructure/Persistence/Eloquent/Estudiantes/Models/EstudianteModel.php
namespace App\Infrastructure\Persistence\Eloquent\Estudiantes\Models;

use Illuminate\Database\Eloquent\Model;

class EstudianteModel extends Model
{
    protected $table = 'estudiantes';
    protected $fillable = ['carnet', 'nombre', 'activo'];
}

// app/Infrastructure/Persistence/Eloquent/Estudiantes/Repositories/EloquentEstudianteRepository.php
namespace App\Infrastructure\Persistence\Eloquent\Estudiantes\Repositories;

use App\Domain\Estudiantes\Entities\Estudiante;
use App\Domain\Estudiantes\Repositories\EstudianteRepository;
use App\Infrastructure\Persistence\Eloquent\Estudiantes\Models\EstudianteModel;

class EloquentEstudianteRepository implements EstudianteRepository
{
    public function findById(int $id): ?Estudiante
    {
        $model = EstudianteModel::find($id);

        return $model ? new Estudiante($model->id, $model->carnet, $model->nombre, $model->activo) : null;
    }

    public function save(Estudiante $estudiante): void
    {
        EstudianteModel::updateOrCreate(
            ['id' => $estudiante->id],
            ['carnet' => $estudiante->carnet, 'nombre' => $estudiante->nombre, 'activo' => $estudiante->activo],
        );
    }
}
```

Luego se registra el binding en `DomainServiceProvider`:

```php
protected array $repositoryBindings = [
    \App\Domain\Estudiantes\Repositories\EstudianteRepository::class =>
        \App\Infrastructure\Persistence\Eloquent\Estudiantes\Repositories\EloquentEstudianteRepository::class,
];
```
