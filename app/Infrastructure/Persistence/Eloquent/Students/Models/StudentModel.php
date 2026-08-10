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

    protected $table = 'students';

    protected $fillable = [
        'user_id', 'national_id', 'name', 'last_name', 'second_last_name', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function studyPlans(): BelongsToMany
    {
        return $this->belongsToMany(StudyPlanModel::class, 'student_study_plan', 'student_id', 'study_plan_id')
            ->withPivot('current_level');
    }

    public function academicRecords(): HasMany
    {
        return $this->hasMany(AcademicRecordModel::class, 'student_id');
    }
}
