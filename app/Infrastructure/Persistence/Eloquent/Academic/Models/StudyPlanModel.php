<?php

namespace App\Infrastructure\Persistence\Eloquent\Academic\Models;

use Database\Factories\StudyPlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyPlanModel extends Model
{
    use HasFactory;

    protected static function newFactory(): StudyPlanFactory
    {
        return StudyPlanFactory::new();
    }

    protected $table = 'planes_estudio';

    protected $fillable = [
        'carrera_id', 'nombre', 'anio_implementacion', 'clasificacion', 'fecha_cierre_matricula',
    ];

    protected $casts = [
        'fecha_cierre_matricula' => 'date',
    ];

    public function career(): BelongsTo
    {
        return $this->belongsTo(CareerModel::class, 'carrera_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(LevelModel::class, 'plan_estudio_id');
    }
}
