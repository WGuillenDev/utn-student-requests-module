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
            'request_id' => RequestModel::factory(),
            'previous_status' => null,
            'new_status' => 'Pending Review',
            'comment' => null,
            'user_id' => null,
            'notified_at' => null,
        ];
    }
}
