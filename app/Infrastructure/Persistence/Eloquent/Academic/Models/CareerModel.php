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

    protected $table = 'carreras';

    protected $fillable = ['nombre', 'activa'];

    protected $casts = [
        'activa' => 'boolean',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(CourseModel::class, 'carrera_id');
    }

    public function studyPlans(): HasMany
    {
        return $this->hasMany(StudyPlanModel::class, 'carrera_id');
    }
}
