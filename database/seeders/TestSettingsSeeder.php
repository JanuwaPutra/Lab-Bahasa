<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TestSettings;

class TestSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default time limits for different test types (in minutes)
        $defaultSettings = [
            [
                'test_type' => 'pretest',
                'time_limit' => 30,
                'language' => 'id'
            ],
            [
                'test_type' => 'pretest',
                'time_limit' => 30,
                'language' => 'en'
            ],
            [
                'test_type' => 'post_test',
                'time_limit' => 45,
                'language' => 'id'
            ],
            [
                'test_type' => 'post_test',
                'time_limit' => 45,
                'language' => 'en'
            ],
            [
                'test_type' => 'placement',
                'time_limit' => 20,
                'language' => 'id'
            ],
            [
                'test_type' => 'placement',
                'time_limit' => 20,
                'language' => 'en'
            ],
            [
                'test_type' => 'listening',
                'time_limit' => 15,
                'language' => 'id'
            ],
            [
                'test_type' => 'listening',
                'time_limit' => 15,
                'language' => 'en'
            ],
            [
                'test_type' => 'reading',
                'time_limit' => 15,
                'language' => 'id'
            ],
            [
                'test_type' => 'reading',
                'time_limit' => 15,
                'language' => 'en'
            ],
            [
                'test_type' => 'speaking',
                'time_limit' => 10,
                'language' => 'id'
            ],
            [
                'test_type' => 'speaking',
                'time_limit' => 10,
                'language' => 'en'
            ],
            [
                'test_type' => 'grammar',
                'time_limit' => 10,
                'language' => 'id'
            ],
            [
                'test_type' => 'grammar',
                'time_limit' => 10,
                'language' => 'en'
            ]
        ];
        
        foreach ($defaultSettings as $setting) {
            TestSettings::updateOrCreate(
                [
                    'test_type' => $setting['test_type'],
                    'language' => $setting['language']
                ],
                [
                    'time_limit' => $setting['time_limit']
                ]
            );
        }
    }
} 