<?php

declare(strict_types=1);

namespace Src\Requests\Request\Application\UseCases;

use Src\Requests\Request\Domain\Contracts\RequestRepositoryInterface;
use Src\Requests\Request\Domain\Entities\Request;
use Src\Requests\Request\Domain\Exceptions\RequestNotFoundException;

/**
 * Docencia capturing the external course's own code/credits while
 * reviewing a Validation request — deliberately separate from
 * ChangeRequestStatusUseCase: saving this data is not itself a
 * decision (Reconocer/No reconocer), so it never touches status or
 * writes to RequestStatusHistory.
 */
final class SaveExternalCourseDataUseCase
{
    public function __construct(
        private readonly RequestRepositoryInterface $repository,
    ) {}

    public function handle(int $requestId, ?string $code, ?int $credits): Request
    {
        $request = $this->repository->find($requestId) ?? throw RequestNotFoundException::withId($requestId);

        $request->setExternalCourseData($code, $credits);

        return $this->repository->save($request);
    }
}
