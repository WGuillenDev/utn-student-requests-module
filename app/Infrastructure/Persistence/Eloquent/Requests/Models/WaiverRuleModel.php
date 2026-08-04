<?php

namespace App\Infrastructure\Persistence\Eloquent\Requests\Models;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use Database\Factories\WaiverRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaiverRuleModel extends Model
{
    use HasFactory;

    protected static function newFactory(): WaiverRuleFactory
    {
        return WaiverRuleFactory::new();
    }

    protected $table = 'reglas_levantamiento';

    protected $fillable = [
        'curso_id', 'orden', 'tipo', 'curso_requisito_id', 'nota_minima', 'minimo_acumulado', 'activo',
    ];

    protected $casts = [
        'nota_minima' => 'decimal:2',
        'activo' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'curso_id');
    }

    public function requiredCourse(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'curso_requisito_id');
    }
}
