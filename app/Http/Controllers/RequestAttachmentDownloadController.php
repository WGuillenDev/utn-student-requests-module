<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Infrastructure\Persistence\Eloquent\Documents\Models\FileModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestModel;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Src\Requests\Request\Domain\Entities\Request as RequestEntity;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a document attached to a Request (ES-01/ES-02's supporting
 * files), reachable from both the Docencia inbox's detail view and the
 * student's own "My requests" screen. Gated by the same RequestPolicy::
 * view() ability the detail modal itself is gated by — a student can
 * only ever reach their own request's files, staff can reach any.
 *
 * Deliberately a plain authenticated route, not a signed URL: the file
 * is only ever linked from an already-authorized page (the request
 * detail modal), so there's no case where an unauthenticated party
 * legitimately holds this link — a signed URL would only add expiry
 * complexity without a real threat it defends against here.
 */
final class RequestAttachmentDownloadController extends Controller
{
    public function __invoke(int $fileId): StreamedResponse|Response
    {
        $file = FileModel::query()->findOrFail($fileId);

        abort_unless($file->fileable_type === RequestModel::class, 404);

        $requestModel = RequestModel::query()->findOrFail($file->fileable_id);

        $requestEntity = RequestEntity::reconstitute(
            id: $requestModel->id,
            studentId: $requestModel->student_id,
            type: $requestModel->type,
            courseId: $requestModel->course_id,
            requiredCourseId: $requestModel->required_course_id,
            originInstitution: $requestModel->origin_institution,
            externalCourse: $requestModel->external_course,
            validationPrecedentId: $requestModel->validation_precedent_id,
            engineResult: $requestModel->engine_result,
            violatedRuleId: $requestModel->violated_rule_id,
            status: $requestModel->status,
            estimatedResolutionDate: $requestModel->estimated_resolution_date?->toDateString(),
            reviewerId: $requestModel->reviewer_id,
        );

        Gate::authorize('view', $requestEntity);

        abort_unless(Storage::disk($file->disk)->exists($file->path), 404);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }
}
