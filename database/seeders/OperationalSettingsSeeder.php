<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OperationalSettingsSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        $settings = [
            [
                'key' => 'late_login_grace_period',
                'value' => '300',
                'description' => 'Grace period for shift start in seconds (default 5 min)',
            ],
            [
                'key' => 'personal_time_threshold',
                'value' => '600',
                'description' => 'Maximum allowed time for ACW/AUX states in seconds (default 15 min)',
            ],
            [
                'key' => 'adherence_alert_threshold',
                'value' => '120',
                'description' => 'Threshold for deviation between real and scheduled state in seconds',
            ],
            [
                'key' => 'default_lunch_minutes',
                'value' => '2700',
                'description' => 'Default lunch duration in seconds (45 min)',
            ],
            [
                'key' => 'default_break_minutes',
                'value' => '900',
                'description' => 'Default break duration in seconds (15 min)',
            ],
        ];

        foreach ($settings as $setting) {
            \DB::table('operational_settings')->updateOrInsert(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
