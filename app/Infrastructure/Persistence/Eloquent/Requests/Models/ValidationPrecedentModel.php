<?php

namespace App\Infrastructure\Persistence\Eloquent\Requests\Models;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use Database\Factories\ValidationPrecedentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationPrecedentModel extends Model
{
    use HasFactory;

    protected static function newFactory(): ValidationPrecedentFactory
    {
        return ValidationPrecedentFactory::new();
    }

    protected $table = 'convalidaciones_historicas';

    protected $fillable = [
        'institucion', 'curso_externo', 'curso_id', 'resultado', 'numero_resolucion',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'curso_id');
    }
}
