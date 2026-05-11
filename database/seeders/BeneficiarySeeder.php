<?php

namespace Database\Seeders;

use App\Models\Beneficiary;
use App\Models\Project;
use App\Models\Training;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BeneficiarySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects  = Project::all();
        $trainings = Training::all();

        // Create 50 beneficiaries and attach them to projects/trainings
        Beneficiary::factory(50)->create()->each(function ($beneficiary) use ($projects, $trainings) {

            // Enroll in 1–2 random projects
            $projects->random(rand(1, 2))->each(function ($project) use ($beneficiary) {
                $beneficiary->projects()->syncWithoutDetaching([
                    $project->id => [
                        'date_enrolled' => now()->subDays(rand(10, 365)),
                        'status'        => 'Active',
                    ],
                ]);
            });

            // Attach to 1–3 random trainings
            $trainings->random(rand(1, 3))->each(function ($training) use ($beneficiary) {
                $beneficiary->trainings()->syncWithoutDetaching([
                    $training->id => [
                        'date_attended'     => now()->subDays(rand(5, 300)),
                        'completion_status' => 'Completed',
                    ],
                ]);
            });
        });
    }
}
