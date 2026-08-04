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

    protected $table = 'periodos_academicos';

    protected $fillable = ['anio', 'cuatrimestre', 'fecha_inicio', 'fecha_fin'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];
}
