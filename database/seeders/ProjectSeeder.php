<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'project_name' => 'Kabuhayan at Kaunlaran ng Kababaihang Pilipino (3KP)',
                'project_code' => 'SLP-3KP-2023',
                'description' => 'Livelihood project targeting women beneficiaries.',
                'date_started' => '2023-01-01',
                'date_ended' => null,
                'fund_source' => 'DSWD Central Office',
            ],
            [
                'project_name' => 'SLP Microenterprise Development',
                'project_code' => 'SLP-MED-2023',
                'description' => 'Supports small business setup for poor households.',
                'date_started' => '2023-03-15',
                'date_ended' => null,
                'fund_source' => 'DSWD Region VI',
            ],
            [
                'project_name' => 'SLP Employment Facilitation',
                'project_code' => 'SLP-EF-2022',
                'description' => 'Links beneficiaries to employment opportunities.',
                'date_started' => '2022-06-01',
                'date_ended' => '2023-06-01',
                'fund_source' => 'LGU',
            ],
        ];

        foreach ($projects as $project) {
            Project::firstOrCreate(['project_code' => $project['project_code']], $project);
        }

        // Additional random projects for testing
        Project::factory(5)->create();
    }
}
