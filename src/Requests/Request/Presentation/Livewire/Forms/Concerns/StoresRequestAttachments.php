<?php

declare(strict_types=1);

namespace Src\Requests\Request\Presentation\Livewire\Forms\Concerns;

use Illuminate\Support\Facades\Storage;
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
    private function storeAttachment(TemporaryUploadedFile $file, string $documentType): RequestAttachment
    {
        $path = $file->store('requests', 'local');

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
