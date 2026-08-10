<?php

namespace App\Infrastructure\Persistence\Eloquent\Academic\Models;

use Database\Factories\AcademicPeriodFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicPeriodModel extends Model
{
    use HasFactory;

    protected static function newFactory(): AcademicPeriodFactory
    {
        return AcademicPeriodFactory::new();
    }

    protected $table = 'academic_periods';

    protected $fillable = ['year', 'term', 'start_date', 'end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];
}
