<?php

namespace App\Infrastructure\Persistence\Eloquent\Students\Models;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\StudyPlanModel;
use App\Models\User;
use Database\Factories\StudentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentModel extends Model
{
    use HasFactory;

    protected static function newFactory(): StudentFactory
    {
        return StudentFactory::new();
    }

    protected $table = 'estudiantes';

    protected $fillable = [
        'user_id', 'cedula', 'nombre', 'primer_apellido', 'segundo_apellido', 'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function studyPlans(): BelongsToMany
    {
        return $this->belongsToMany(StudyPlanModel::class, 'estudiante_plan', 'estudiante_id', 'plan_estudio_id')
            ->withPivot('nivel_actual');
    }

    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecordModel::class, 'estudiante_id');
    }
}
