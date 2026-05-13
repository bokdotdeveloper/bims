<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Training;
use DateTimeImmutable;
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

        $barangays = ['Poblacion', 'Cabugao', 'Malamhati'];
        $durations = [4, 6, 8, 16, 24];

        $firstNames = ['Maria', 'Juan', 'Ana', 'Pedro', 'Rosa', 'Carlos', 'Liza'];
        $lastNames = ['Santos', 'Cruz', 'Reyes', 'Garcia', 'Lopez', 'Torres', 'Flores'];

        $type = $types[array_rand($types)];
        $conductedTs = random_int(strtotime('-2 years'), time());
        $conducted = (new DateTimeImmutable)->setTimestamp($conductedTs);

        $facilitator = $firstNames[array_rand($firstNames)].' '.$lastNames[array_rand($lastNames)];

        return [
            'training_title' => $type.' Training',
            'training_type' => $type,
            'facilitator' => $facilitator,
            'venue' => 'Barangay Hall, '.$barangays[array_rand($barangays)],
            'date_conducted' => $conducted->format('Y-m-d'),
            'duration_hours' => $durations[array_rand($durations)],
            'project_id' => Project::query()->inRandomOrder()->value('id')
                ?? Project::query()->value('id'),
        ];
    }
}
