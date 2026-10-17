<?php

namespace Database\Seeders;

use App\Models\Assessment;
use Illuminate\Database\Seeder;

class DiscAssessmentSeeder extends Seeder
{
    public function run(): void
    {
        $assessment = Assessment::firstOrCreate(
            ['slug' => 'disc'],
            [
                'name' => 'DISC',
                'description' => 'An independently developed personality assessment based on the four DISC dimensions.',
                'is_active' => false,
            ]
        );

        $assessment->versions()->firstOrCreate(
            ['version' => '1.0'],
            [
                'scoring_config' => null,
                'published_at' => null,
            ]
        );
    }
}