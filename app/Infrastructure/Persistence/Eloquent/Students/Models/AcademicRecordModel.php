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

    protected $table = 'academic_records';

    protected $fillable = [
        'student_id', 'course_id', 'academic_period_id', 'status', 'grade', 'equivalence_id',
    ];

    protected $casts = [
        'grade' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentModel::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }

    public function academicPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicPeriodModel::class, 'academic_period_id');
    }
}
