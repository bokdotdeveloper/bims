<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $projects = [
            'Kabuhayan at Kaunlaran ng Kababaihang Pilipino (3KP)',
            'Sustainable Livelihood Program - Microenterprise',
            'Sustainable Livelihood Program - Employment Facilitation',
            'Livelihood Seeding Program',
            'Negosyo sa Barangay',
            'Kapital Access for Young Agripreneurs (KAYA)',
        ];

        $start = $this->faker->dateTimeBetween('-3 years', '-6 months');

        return [
            'project_name' => $this->faker->randomElement($projects),
            'project_code' => 'SLP-' . strtoupper($this->faker->unique()->bothify('??###')),
            'description'  => $this->faker->sentence(12),
            'date_started' => $start->format('Y-m-d'),
            'date_ended'   => $this->faker->optional(0.4)->dateTimeBetween($start, 'now')?->format('Y-m-d'),
            'fund_source'  => $this->faker->randomElement(['DSWD Central Office', 'LGU', 'DSWD Region VI', 'Other']),
            'is_active'    => true,
        ];
    }
}
