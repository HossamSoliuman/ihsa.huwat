<?php

namespace Database\Seeders;

use App\Models\InformationSubmission;
use Illuminate\Database\Seeder;

class InformationSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        InformationSubmission::factory()->count(8)->create();
    }
}
