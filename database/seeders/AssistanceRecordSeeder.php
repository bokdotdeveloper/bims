<?php

namespace Database\Seeders;

use App\Models\AssistanceRecord;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssistanceRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AssistanceRecord::factory(80)->create();
    }
}
