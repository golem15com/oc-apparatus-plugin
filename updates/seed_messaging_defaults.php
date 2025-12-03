<?php namespace Golem15\Apparatus\Updates;

use Seeder;
use Golem15\Apparatus\Models\Settings;

/**
 * Seed default messaging settings for Apparatus plugin
 *
 * This ensures flash messages have sensible defaults:
 * - Position: topRight
 * - Theme: tailwind (or queststream if you prefer)
 * - Timeout: 5 seconds
 * - Dismiss queue: true
 */
class SeedMessagingDefaults extends Seeder
{
    public function run()
    {
        $settings = Settings::instance();

        // Only seed if messaging settings are not already configured
        if (empty($settings->value) || !isset($settings->value['layout'])) {
            $defaults = [
                'layout' => 'topRight',
                'theme' => 'queststream',
                'openAnimation' => 'animated fadeIn',
                'closeAnimation' => 'animated fadeOut',
                'template' => null,
                'timeout' => 5,
                'dismissQueue' => true,
                'force' => false,
                'modal' => false,
                'maxVisible' => 5,
            ];

            $settings->value = array_merge($settings->value ?? [], $defaults);
            $settings->save();

            if ($this->command) {
                $this->command->info('Apparatus: Messaging defaults seeded successfully.');
            }
        } else {
            if ($this->command) {
                $this->command->info('Apparatus: Messaging settings already configured, skipping seed.');
            }
        }
    }
}
