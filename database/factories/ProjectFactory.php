<?php

namespace Database\Factories;

use App\Models\Project;
use DateTimeImmutable;
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

        $fundSources = ['DSWD Central Office', 'LGU', 'DSWD Region VI', 'Other'];

        $startTs = random_int(strtotime('-3 years'), strtotime('-6 months'));
        $start = (new DateTimeImmutable)->setTimestamp($startTs);

        $endDate = null;
        if (random_int(1, 100) <= 40) {
            $endTs = random_int($startTs, time());
            $endDate = (new DateTimeImmutable)->setTimestamp($endTs)->format('Y-m-d');
        }

        $codeSuffix = strtoupper(bin2hex(random_bytes(3)));

        return [
            'project_name' => $projects[array_rand($projects)],
            'project_code' => 'SLP-'.$codeSuffix,
            'description' => 'Seeded demo project for testing and development.',
            'date_started' => $start->format('Y-m-d'),
            'date_ended' => $endDate,
            'fund_source' => $fundSources[array_rand($fundSources)],
        ];
    }
}
