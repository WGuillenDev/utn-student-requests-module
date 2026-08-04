<?php

namespace App\Infrastructure\Persistence\Eloquent\Requests\Models;

use App\Models\User;
use Database\Factories\RequestStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestStatusHistoryModel extends Model
{
    use HasFactory;

    protected static function newFactory(): RequestStatusHistoryFactory
    {
        return RequestStatusHistoryFactory::new();
    }

    protected $table = 'solicitud_estados_historial';

    /**
     * This table only has `created_at` (a point-in-time event, never "updated").
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'solicitud_id', 'estado_anterior', 'estado_nuevo', 'comentario', 'user_id', 'notificado_at',
    ];

    protected $casts = [
        'notificado_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(RequestModel::class, 'solicitud_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
