<?php

namespace App\Infrastructure\Persistence\Eloquent\Academic\Models;

use Database\Factories\CareerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CareerModel extends Model
{
    use HasFactory;

    protected static function newFactory(): CareerFactory
    {
        return CareerFactory::new();
    }

    protected $table = 'careers';

    protected $fillable = ['name', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(CourseModel::class, 'career_id');
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlanModel::class, 'career_id');
    }
}
