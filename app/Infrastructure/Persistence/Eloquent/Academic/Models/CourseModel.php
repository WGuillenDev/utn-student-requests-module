<?php

namespace App\Infrastructure\Persistence\Eloquent\Academic\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CourseModel extends Model
{
    use HasFactory;

    protected static function newFactory(): CourseFactory
    {
        return CourseFactory::new();
    }

    protected $table = 'courses';

    protected $fillable = [
        'career_id', 'code', 'name', 'is_service', 'is_bottleneck',
        'requires_lab', 'lab_type', 'active',
    ];

    protected $casts = [
        'is_service' => 'boolean',
        'is_bottleneck' => 'boolean',
        'requires_lab' => 'boolean',
        'active' => 'boolean',
    ];

    public function career(): BelongsTo
    {
        return $this->belongsTo(CareerModel::class, 'career_id');
    }

    public function levels(): BelongsToMany
    {
        return $this->belongsToMany(LevelModel::class, 'course_level', 'course_id', 'level_id')
            ->withPivot('credits');
    }
}
