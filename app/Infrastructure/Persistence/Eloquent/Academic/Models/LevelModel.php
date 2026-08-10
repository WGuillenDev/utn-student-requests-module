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

    protected $table = 'levels';

    protected $fillable = ['study_plan_id', 'number'];

    public function studyPlan(): BelongsTo
    {
        return $this->belongsTo(StudyPlanModel::class, 'study_plan_id');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(CourseModel::class, 'course_level', 'level_id', 'course_id')
            ->withPivot('credits');
    }
}
