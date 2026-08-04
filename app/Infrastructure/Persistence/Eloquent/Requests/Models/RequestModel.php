<?php

namespace App\Infrastructure\Persistence\Eloquent\Requests\Models;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Models\User;
use Database\Factories\RequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestModel extends Model
{
    use HasFactory;

    protected static function newFactory(): RequestFactory
    {
        return RequestFactory::new();
    }

    protected $table = 'solicitudes';

    protected $fillable = [
        'estudiante_id', 'tipo', 'curso_id', 'curso_requisito_id',
        'institucion_origen', 'curso_externo', 'convalidacion_historica_id',
        'resultado_motor', 'regla_incumplida_id', 'estado',
        'fecha_estimada_resolucion', 'revisor_id',
    ];

    protected $casts = [
        'fecha_estimada_resolucion' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentModel::class, 'estudiante_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'curso_id');
    }

    public function requiredCourse(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'curso_requisito_id');
    }

    public function validationPrecedent(): BelongsTo
    {
        return $this->belongsTo(ValidationPrecedentModel::class, 'convalidacion_historica_id');
    }

    public function violatedRule(): BelongsTo
    {
        return $this->belongsTo(WaiverRuleModel::class, 'regla_incumplida_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisor_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(RequestStatusHistoryModel::class, 'solicitud_id');
    }
}
