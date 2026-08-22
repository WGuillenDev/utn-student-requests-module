<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire\Forms\Concerns;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Src\Requests\Request\Domain\ValueObjects\RequestAttachment;

/**
 * Shared by WaiverRequestForm and ValidationRequestForm — moves a
 * Livewire temporary upload to permanent storage and describes it as a
 * Domain RequestAttachment VO. Lives in Presentation (not Domain/
 * Application) precisely because it touches Illuminate/Livewire file
 * classes, which those layers may never import.
 */
trait StoresRequestAttachments
{
    /**
     * Names the stored copy explicitly (rather than $file->store(),
     * which derives it from the temporary upload's own hash) so calling
     * this twice for the same uploaded file — one Validation submission
     * can create several Request rows sharing one pool of documents —
     * produces distinct paths instead of colliding with the `files`
     * table's (disk, path) unique constraint.
     */
    private function storeAttachment(TemporaryUploadedFile $file, string $documentType): RequestAttachment
    {
        $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('requests', $filename, 'local');

        return new RequestAttachment(
            documentType: $documentType,
            originalName: $file->getClientOriginalName(),
            disk: 'local',
            path: $path,
            mimeType: $file->getMimeType(),
            sizeBytes: $file->getSize(),
            hashSha256: hash_file('sha256', Storage::disk('local')->path($path)),
        );
    }
}
