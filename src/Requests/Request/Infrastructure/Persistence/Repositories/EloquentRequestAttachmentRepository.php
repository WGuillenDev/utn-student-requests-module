<?php

declare(strict_types=1);

namespace Src\Requests\Request\Infrastructure\Persistence\Repositories;

use App\Infrastructure\Persistence\Eloquent\Documents\Models\FileModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Src\Requests\Request\Domain\Contracts\RequestAttachmentRepositoryInterface;

final class EloquentRequestAttachmentRepository implements RequestAttachmentRepositoryInterface
{
    public function attach(int $requestId, array $attachments): void
    {
        foreach ($attachments as $attachment) {
            FileModel::query()->create([
                'uuid' => (string) Str::uuid(),
                'user_id' => Auth::id(),
                'fileable_type' => RequestModel::class,
                'fileable_id' => $requestId,
                'document_type' => $attachment->documentType,
                'original_name' => $attachment->originalName,
                'disk' => $attachment->disk,
                'path' => $attachment->path,
                'mime_type' => $attachment->mimeType,
                'size_bytes' => $attachment->sizeBytes,
                'hash_sha256' => $attachment->hashSha256,
            ]);
        }
    }
}
