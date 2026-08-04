# Application

Orchestrates the system's use cases. Depends on `Domain`, never the other way around. It doesn't know whether the caller is a Livewire component, an API controller, or a console command.

## Convention per module

```
app/Application/<Module>/
├── UseCases/    # One class per use case (e.g. CreateStudent, ListStudents)
└── DTOs/         # Input/output data transfer objects for the use cases
```

## Rules

- One use case = one business action, with a single public method (`handle()` or `__invoke()`).
- Receives and returns this layer's own DTOs, never Eloquent models or Laravel's `Request`.
- Depends on the repository **interfaces** defined in `Domain`, resolved via dependency injection (the concrete binding is registered in `app/Infrastructure/Providers/DomainServiceProvider.php`).

## Example (referential, not implemented)

```php
// app/Application/Students/UseCases/CreateStudent.php
namespace App\Application\Students\UseCases;

use App\Domain\Students\Entities\Student;
use App\Domain\Students\Repositories\StudentRepository;

final class CreateStudent
{
    public function __construct(
        private readonly StudentRepository $students,
    ) {}

    public function handle(CreateStudentDTO $dto): Student
    {
        $student = new Student(null, $dto->studentId, $dto->name, active: true);

        $this->students->save($student);

        return $student;
    }
}
```

The entry adapter (a controller or Livewire component) builds the DTO from the `Request`/form and calls the use case; it never contains business logic.
