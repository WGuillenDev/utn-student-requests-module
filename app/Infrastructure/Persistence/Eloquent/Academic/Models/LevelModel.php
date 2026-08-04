<?php

namespace App\Infrastructure\Persistence\Eloquent\Academic\Models;

use Database\Factories\LevelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LevelModel extends Model
{
    use HasFactory;

    protected static function newFactory(): LevelFactory
    {
        return LevelFactory::new();
    }

    protected $table = 'niveles';

    protected $fillable = ['plan_estudio_id', 'numero'];

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlanModel::class, 'plan_estudio_id');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(CourseModel::class, 'curso_nivel', 'nivel_id', 'curso_id')
            ->withPivot('creditos');
    }
}
