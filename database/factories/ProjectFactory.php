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

        $start = \fake()->dateTimeBetween('-3 years', '-6 months');

        return [
            'project_name' => \fake()->randomElement($projects),
            'project_code' => 'SLP-'.strtoupper(\fake()->unique()->bothify('??###')),
            'description' => \fake()->sentence(12),
            'date_started' => $start->format('Y-m-d'),
            'date_ended' => \fake()->optional(0.4)->dateTimeBetween($start, 'now')?->format('Y-m-d'),
            'fund_source' => \fake()->randomElement(['DSWD Central Office', 'LGU', 'DSWD Region VI', 'Other']),
        ];
    }
}
