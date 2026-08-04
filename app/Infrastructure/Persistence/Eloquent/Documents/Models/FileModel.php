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

    protected $table = 'archivos';

    protected $fillable = [
        'uuid', 'user_id', 'archivable_type', 'archivable_id', 'tipo_documento',
        'nombre_original', 'disco', 'ruta', 'mime_type', 'tamano_bytes', 'hash_sha256',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * `archivable` matches the archivable_type/archivable_id column prefix
     * from the official schema (Section 8.1) — do not rename without also
     * changing the migration's morphs() column prefix.
     */
    public function archivable(): MorphTo
    {
        return $this->morphTo();
    }
}
