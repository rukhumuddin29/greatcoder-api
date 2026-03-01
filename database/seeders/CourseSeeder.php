<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'name' => 'Master in Full Stack Development',
                'code' => 'FSD-001',
                'description' => 'Comprehensive course covering HTML, CSS, JavaScript, React, and Laravel.',
                'category' => 'IT',
                'original_price' => 45000,
                'offer_price' => 34999,
                'duration_weeks' => 24,
                'mode' => 'hybrid',
                'status' => 'active'
            ],
            [
                'name' => 'UI/UX Design Essentials',
                'code' => 'UIX-001',
                'description' => 'Learn Figma, design principles, and user research.',
                'category' => 'IT',
                'original_price' => 25000,
                'offer_price' => 19999,
                'duration_weeks' => 12,
                'mode' => 'online',
                'status' => 'active'
            ],
            [
                'name' => 'Digital Marketing Pro',
                'code' => 'DM-001',
                'description' => 'Master SEO, SEM, SMM and Content Strategy.',
                'category' => 'Marketing',
                'original_price' => 30000,
                'offer_price' => 24999,
                'duration_weeks' => 16,
                'mode' => 'offline',
                'status' => 'active'
            ]
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(['code' => $course['code']], $course);
        }
    }
}
