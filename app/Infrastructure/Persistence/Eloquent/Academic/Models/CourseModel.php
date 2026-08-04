<?php

namespace App\Infrastructure\Persistence\Eloquent\Academic\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseModel extends Model
{
    use HasFactory;

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }

    protected $table = 'cursos';

    protected $fillable = [
        'carrera_id', 'codigo', 'nombre', 'es_servicio', 'es_cuello_botella',
        'requiere_laboratorio', 'tipo_laboratorio', 'activo',
    ];

    protected $casts = [
        'es_servicio' => 'boolean',
        'es_cuello_botella' => 'boolean',
        'requiere_laboratorio' => 'boolean',
        'activo' => 'boolean',
    ];

    public function career(): BelongsTo
    {
        return $this->belongsTo(CareerModel::class, 'carrera_id');
    }

    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(LevelModel::class, 'curso_nivel', 'curso_id', 'nivel_id')
            ->withPivot('creditos');
    }
}
