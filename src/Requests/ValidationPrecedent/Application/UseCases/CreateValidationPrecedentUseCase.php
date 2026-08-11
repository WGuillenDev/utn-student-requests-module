<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Application\UseCases;

use Src\Requests\ValidationPrecedent\Application\DTOs\ValidationPrecedentDTO;
use Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface;
use Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent;

/**
 * Single-purpose orchestrator (SRP): turns a ValidationPrecedentDTO into
 * a persisted ValidationPrecedent. No business logic beyond what the
 * Domain already encapsulates — same shape as CreateRoleUseCase.
 */
final class CreateValidationPrecedentUseCase
{
    public function __construct(
        private readonly ValidationPrecedentRepositoryInterface $repository,
    ) {}

    public function handle(ValidationPrecedentDTO $dto): ValidationPrecedent
    {
        $precedent = ValidationPrecedent::create(
            institution: $dto->institution,
            externalCourse: $dto->externalCourse,
            courseId: $dto->courseId,
            result: $dto->result,
            resolutionNumber: $dto->resolutionNumber,
        );

        return $this->repository->save($precedent);
    }
}
