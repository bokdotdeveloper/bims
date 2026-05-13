<?php

namespace Database\Factories;

use App\Models\AssistanceRecord;
use App\Models\Beneficiary;
use App\Models\Project;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssistanceRecordFactory extends Factory
{
    protected $model = AssistanceRecord::class;

    public function definition(): array
    {
        $types = [
            'Livelihood Kit', 'Cash Grant', 'Starter Capital',
            'Training Allowance', 'Equipment', 'Seeds and Fertilizer',
        ];

        $firstNames = ['Maria', 'Juan', 'Ana', 'Pedro', 'Rosa'];
        $lastNames = ['Santos', 'Cruz', 'Reyes', 'Garcia', 'Lopez'];

        $releasedTs = random_int(strtotime('-2 years'), time());
        $released = (new DateTimeImmutable)->setTimestamp($releasedTs);

        $amount = random_int(1, 10) <= 7
            ? round(random_int(50_000, 5_000_000) / 100, 2)
            : null;

        $remarks = random_int(1, 10) <= 5
            ? 'Assistance record seeded for testing.'
            : null;

        return [
            'beneficiary_id' => Beneficiary::query()->inRandomOrder()->value('id'),
            'project_id' => Project::query()->inRandomOrder()->value('id'),
            'assistance_type' => $types[array_rand($types)],
            'amount' => $amount,
            'date_released' => $released->format('Y-m-d'),
            'released_by' => $firstNames[array_rand($firstNames)].' '.$lastNames[array_rand($lastNames)],
            'remarks' => $remarks,
        ];
    }
}
