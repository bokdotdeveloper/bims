<?php

namespace Database\Factories;

use App\Models\AssistanceRecord;
use App\Models\Beneficiary;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssistanceRecordFactory extends Factory
{
    protected $model = AssistanceRecord::class;

    public function definition(): array
    {
        return [
            'beneficiary_id'  => Beneficiary::inRandomOrder()->value('id'),
            'project_id'      => Project::inRandomOrder()->value('id'),
            'assistance_type' => $this->faker->randomElement([
                'Livelihood Kit', 'Cash Grant', 'Starter Capital',
                'Training Allowance', 'Equipment', 'Seeds and Fertilizer',
            ]),
            'amount'          => $this->faker->optional(0.7)->randomFloat(2, 500, 50000),
            'date_released'   => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'released_by'     => $this->faker->name(),
            'remarks'         => $this->faker->optional()->sentence(),
        ];
    }
}
