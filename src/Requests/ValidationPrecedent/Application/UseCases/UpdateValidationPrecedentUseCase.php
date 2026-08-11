<?php

declare(strict_types=1);

namespace Src\Requests\ValidationPrecedent\Application\UseCases;

use Src\Requests\ValidationPrecedent\Application\DTOs\ValidationPrecedentDTO;
use Src\Requests\ValidationPrecedent\Domain\Contracts\ValidationPrecedentRepositoryInterface;
use Src\Requests\ValidationPrecedent\Domain\Entities\ValidationPrecedent;
use Src\Requests\ValidationPrecedent\Domain\Exceptions\ValidationPrecedentNotFoundException;

final class UpdateValidationPrecedentUseCase
{
    public function __construct(
        private readonly ValidationPrecedentRepositoryInterface $repository,
    ) {}

    public function handle(int $id, ValidationPrecedentDTO $dto): ValidationPrecedent
    {
        $precedent = $this->repository->find($id) ?? throw ValidationPrecedentNotFoundException::withId($id);

        $precedent->update(
            institution: $dto->institution,
            externalCourse: $dto->externalCourse,
            courseId: $dto->courseId,
            result: $dto->result,
            resolutionNumber: $dto->resolutionNumber,
        );

        return $this->repository->save($precedent);
    }
}
