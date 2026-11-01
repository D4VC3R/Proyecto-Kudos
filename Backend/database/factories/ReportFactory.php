<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'reporter_id' => User::factory(),
            'reportable_type' => User::class,
            'reportable_id' => User::factory(),
            'reason' => 'spam',
            'details' => null,
            'status' => Report::STATUS_PENDING,
        ];
    }
}
