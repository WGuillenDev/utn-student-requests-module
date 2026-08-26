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
 * Streams a document attached to a Request, reachable from both the staff
 * inbox and the student's own screen. Gated by the same
 * RequestPolicy::view() ability as the detail modal, so a student reaches
 * only their own files while staff reach any.
 *
 * A plain authenticated route rather than a signed URL: the link is only
 * ever rendered on an already-authorized page, so signing would add
 * expiry handling without defending against a real threat here.
 */
final class RequestAttachmentDownloadController extends Controller
{
    /**
     * Content-Disposition: inline — the browser renders the PDF/image
     * directly instead of prompting to save it.
     */
    public function preview(int $fileId): StreamedResponse|Response
    {
        $file = $this->resolveFile($fileId);

        return Storage::disk($file->disk)->response($file->path, $file->original_name);
    }

    /**
     * Content-Disposition: attachment — forces the save dialog.
     */
    public function download(int $fileId): StreamedResponse|Response
    {
        $file = $this->resolveFile($fileId);

        return Storage::disk($file->disk)->download($file->path, $file->original_name);
    }

    /**
     * Resolves the file, confirms it belongs to a Request rather than some
     * other fileable type, and authorizes it. Shared by both actions,
     * which differ only in how the file is streamed back.
     */
    private function resolveFile(int $fileId): FileModel
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
            waiverJustification: $requestModel->waiver_justification,
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

        return $file;
    }
}
