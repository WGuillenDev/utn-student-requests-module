<?php

namespace App\Infrastructure\Persistence\Eloquent\Requests\Models;

use App\Infrastructure\Persistence\Eloquent\Academic\Models\CourseModel;
use App\Infrastructure\Persistence\Eloquent\Documents\Models\FileModel;
use App\Infrastructure\Persistence\Eloquent\Students\Models\StudentModel;
use App\Models\User;
use Database\Factories\RequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RequestModel extends Model
{
    use HasFactory;

    protected static function newFactory(): RequestFactory
    {
        return RequestFactory::new();
    }

    protected $table = 'requests';

    protected $fillable = [
        'student_id', 'type', 'course_id', 'required_course_id', 'waiver_justification',
        'origin_institution', 'external_course', 'external_course_code', 'external_course_credits',
        'validation_precedent_id', 'engine_result', 'violated_rule_id', 'status',
        'estimated_resolution_date', 'reviewer_id',
    ];

    protected $casts = [
        'estimated_resolution_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentModel::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }

    public function requiredCourse(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'required_course_id');
    }

    public function validationPrecedent(): BelongsTo
    {
        return $this->belongsTo(ValidationPrecedentModel::class, 'validation_precedent_id');
    }

    public function violatedRule(): BelongsTo
    {
        return $this->belongsTo(WaiverRuleModel::class, 'violated_rule_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(RequestStatusHistoryModel::class, 'request_id');
    }

    public function files(): MorphMany
    {
        return $this->morphMany(FileModel::class, 'fileable');
    }
}
