<?php

namespace Database\Factories;

use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestModel;
use App\Infrastructure\Persistence\Eloquent\Requests\Models\RequestStatusHistoryModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RequestStatusHistoryModel>
 */
class RequestStatusHistoryFactory extends Factory
{
    protected $model = RequestStatusHistoryModel::class;

    public function definition(): array
    {
        return [
            'solicitud_id' => RequestModel::factory(),
            'estado_anterior' => null,
            'estado_nuevo' => 'Pendiente de revisión',
            'comentario' => null,
            'user_id' => null,
            'notificado_at' => null,
        ];
    }
}
