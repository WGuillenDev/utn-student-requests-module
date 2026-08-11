<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Application\UseCases;

use Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface;
use Src\Requests\ValidationPrecedent\Domain\Exceptions\ValidationPrecedentNotFoundException;

/**
 * No extra protection is implemented here on purpose: `course_id` on
 * `validation_precedents` is `restrictOnDelete()` at the database level
 * already (see the requests-module migration), so the database itself
 * refuses to delete a course that still has precedents attached. That
 * constraint doesn't apply to deleting the precedent itself — this
 * UseCase has nothing extra to enforce. Any SQL-level failure (e.g. a
 * FK violation from an unrelated path) surfaces as a QueryException,
 * which WaiverRuleComponent/ValidationPrecedentComponent::delete()
 * catches to show a toast instead of a raw 500.
 */
final class DeleteValidationPrecedentUseCase
{
    public function __construct(
        private readonly ValidationPrecedentRepositoryInterface $repository,
    ) {}

    public function handle(int $id): void
    {
        $this->repository->find($id) ?? throw ValidationPrecedentNotFoundException::withId($id);

        $this->repository->delete($id);
    }
}
