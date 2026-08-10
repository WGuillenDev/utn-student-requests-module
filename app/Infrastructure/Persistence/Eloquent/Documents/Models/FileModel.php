<?php

namespace App\Infrastructure\Persistence\Eloquent\Documents\Models;

use App\Models\User;
use Database\Factories\FileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FileModel extends Model
{
    use HasFactory;

    protected static function newFactory(): FileFactory
    {
        return FileFactory::new();
    }

    protected $table = 'files';

    protected $fillable = [
        'uuid', 'user_id', 'fileable_type', 'fileable_id', 'document_type',
        'original_name', 'disk', 'path', 'mime_type', 'size_bytes', 'hash_sha256',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * `fileable` matches the fileable_type/fileable_id column prefix
     * from the migration's morphs() call — do not rename without also
     * changing the migration's morph column prefix.
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}
