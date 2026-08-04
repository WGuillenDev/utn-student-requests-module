<?php

namespace App\Infrastructure\Persistence\Eloquent\Students\Models;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\AcademicPeriodModel;
use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use Database\Factories\AcademicRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicRecordModel extends Model
{
    use HasFactory;

    protected static function newFactory(): AcademicRecordFactory
    {
        return AcademicRecordFactory::new();
    }

    protected $table = 'historial_academico';

    protected $fillable = [
        'estudiante_id', 'curso_id', 'periodo_academico_id', 'estado', 'nota', 'equiparacion_id',
    ];

    protected $casts = [
        'nota' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentModel::class, 'estudiante_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'curso_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriodModel::class, 'periodo_academico_id');
    }
}
