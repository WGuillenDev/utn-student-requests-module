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

    protected $table = 'waiver_rules';

    protected $fillable = [
        'course_id', 'order', 'type', 'required_course_id', 'minimum_grade', 'minimum_accumulated', 'active',
    ];

    protected $casts = [
        'minimum_grade' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function course(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'course_id');
    }

    public function requiredCourse(): BelongsTo
    {
        return $this->belongsTo(CourseModel::class, 'required_course_id');
    }
}
