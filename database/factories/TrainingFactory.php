<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrainingFactory extends Factory
{
    protected $model = Training::class;

    public function definition(): array
    {
        $types = [
            'Entrepreneurship', 'Financial Literacy', 'Livelihood Skills',
            'Values Formation', 'Cooperative Management', 'Basic Accounting',
        ];

        return [
            'training_title'  => $this->faker->randomElement($types) . ' Training',
            'training_type'   => $this->faker->randomElement($types),
            'facilitator'     => $this->faker->name(),
            'venue'           => 'Barangay Hall, ' . $this->faker->randomElement(['Poblacion', 'Cabugao', 'Malamhati']),
            'date_conducted'  => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'duration_hours'  => $this->faker->randomElement([4, 6, 8, 16, 24]),
            'project_id'      => Project::inRandomOrder()->value('id'),
        ];
    }
}
