# Domain

Layer of pure business rules. Depends on neither Laravel nor any other layer.

## Convention per module

```
app/Domain/<Module>/
├── Entities/         # Entities / aggregates (plain PHP classes, no Eloquent)
├── ValueObjects/      # Immutable value objects (Email, StudentId, etc.)
├── Repositories/       # Repository interfaces (outbound ports)
├── Services/          # Domain services (logic that doesn't belong to a single entity)
├── Events/            # Domain events
└── Exceptions/        # Domain-specific exceptions
```

## Rules

- Zero `use Illuminate\...`. No Eloquent, Facades, Request, etc. here.
- Entities know nothing about the database: no `save()`, no `find()`, no Eloquent casts.
- Repositories are defined here as **interfaces** (the "port"); the Eloquent implementation lives in `app/Infrastructure/Persistence`.
- If a business rule can be tested without booting Laravel, it belongs in this layer.

## Example (referential, not implemented)

```php
// app/Domain/Students/Entities/Student.php
namespace App\Domain\Students\Entities;

final class Student
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $studentId,
        public readonly string $name,
        public readonly bool $active,
    ) {}
}

// app/Domain/Students/Repositories/StudentRepository.php
namespace App\Domain\Students\Repositories;

use App\Domain\Students\Entities\Student;

interface StudentRepository
{
    public function findById(int $id): ?Student;
    public function save(Student $student): void;
}
```
