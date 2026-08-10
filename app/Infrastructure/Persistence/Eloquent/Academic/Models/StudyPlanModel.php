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

    protected $table = 'study_plans';

    protected $fillable = [
        'career_id', 'name', 'implementation_year', 'classification', 'enrollment_closing_date',
    ];

    protected $casts = [
        'enrollment_closing_date' => 'date',
    ];

    public function career(): BelongsTo
    {
        return $this->belongsTo(CareerModel::class, 'career_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(LevelModel::class, 'study_plan_id');
    }
}
