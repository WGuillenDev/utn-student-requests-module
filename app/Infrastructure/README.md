# Infrastructure

The adapters: everything that connects the domain/application to the outside world (database, HTTP, UI, external services). The only layer allowed to depend on Laravel.

## Structure

```
app/Infrastructure/
├── Persistence/
│   └── Eloquent/<Module>/
│       ├── Models/        # Eloquent models (an infrastructure detail, not the domain)
│       └── Repositories/   # Implement the interfaces from app/Domain/<Module>/Repositories
├── Http/
│   └── Controllers/<Module>/   # Controllers, if the module exposes an API
└── Providers/
    └── DomainServiceProvider.php   # Interface -> implementation bindings
```

## Adapters that already exist in the project (do not move)

- `app/Http/Controllers` and `resources/views/pages/**` (single-file Livewire components) are also entry adapters — that's how Laravel/Livewire use them by convention, and that's where they stay. The authentication scaffold's own (Fortify, login, 2FA, passkeys, profile) are not touched.
- `app/Models/User.php` is infrastructure tied to authentication (Fortify, `config/auth.php`, `PasskeyAuthenticatable`, etc.) and stays where it is.

## Convention for new business modules

The Eloquent models and concrete repositories for **your** modules (the ones you're going to build) go under `Persistence/Eloquent/<Module>`, not in `app/Models`. The Eloquent repository translates between the Eloquent model and the domain entity; the rest of the app never sees the Eloquent model directly, only the entity.

## Example (referential, not implemented)

```php
// app/Infrastructure/Persistence/Eloquent/Students/Models/StudentModel.php
namespace App\Infrastructure\Persistence\Eloquent\Students\Models;

use Illuminate\Database\Eloquent\Model;

class StudentModel extends Model
{
    protected $table = 'estudiantes';
    protected $fillable = ['cedula', 'nombre', 'primer_apellido', 'segundo_apellido', 'activo'];
}

// app/Infrastructure/Persistence/Eloquent/Students/Repositories/EloquentStudentRepository.php
namespace App\Infrastructure\Persistence\Eloquent\Students\Repositories;

use App\Domain\Students\Entities\Student;
use App\Domain\Students\Repositories\StudentRepository;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;

class EloquentStudentRepository implements StudentRepository
{
    public function findById(int $id): ?Student
    {
        $model = StudentModel::find($id);

        return $model ? new Student($model->id, $model->cedula, $model->nombre, $model->activo) : null;
    }

    public function save(Student $student): void
    {
        StudentModel::updateOrCreate(
            ['id' => $student->id],
            ['cedula' => $student->studentId, 'nombre' => $student->name, 'activo' => $student->active],
        );
    }
}
```

Note the boundary crossing here: the Eloquent model's columns (`cedula`, `nombre`, `activo`) match the official Spanish database schema, but the Domain entity's properties (`studentId`, `name`, `active`) are plain English PHP identifiers — the repository is exactly the translation point between the two, so the Domain layer never has to know the real column names.

The binding is then registered in `DomainServiceProvider`:

```php
protected array $repositoryBindings = [
    \App\Domain\Students\Repositories\StudentRepository::class =>
        \App\Infrastructure\Persistence\Eloquent\Students\Repositories\EloquentStudentRepository::class,
];
```
