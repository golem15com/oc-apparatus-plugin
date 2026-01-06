<?php namespace Golem15\Apparatus\Console;

use Illuminate\Console\Command;
use System\Models\MailTemplate;
use System\Models\MailLayout;
use System\Models\MailPartial;
use View;

/**
 * Mail Reset Command
 *
 * Resets mail templates from database custom versions back to plugin file versions.
 * Sets is_custom=0 and refills from plugin files.
 *
 * @package Golem15\Apparatus\Console
 * @author Golem15
 */
class MailResetCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'apparatus:mail-reset
                            {code? : Specific template code to reset}
                            {--plugin= : Reset all templates for a plugin (e.g., golem15.user)}
                            {--all : Reset all custom templates}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset mail templates to plugin file versions (removes custom overrides)';

    /**
     * Reset statistics
     *
     * @var array
     */
    protected $stats = [
        'templates' => 0,
        'layouts' => 0,
        'partials' => 0,
    ];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Mail Template Reset');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Validate arguments
        $code = $this->argument('code');
        $plugin = $this->option('plugin');
        $all = $this->option('all');

        if (!$code && !$plugin && !$all) {
            $this->error('You must specify one of: template code, --plugin, or --all');
            $this->newLine();
            $this->comment('Examples:');
            $this->line('  php artisan apparatus:mail-reset golem15.user::activate');
            $this->line('  php artisan apparatus:mail-reset --plugin=golem15.user');
            $this->line('  php artisan apparatus:mail-reset --all');
            $this->newLine();
            return 1;
        }

        // Build query based on arguments
        if ($code) {
            $this->resetSingleTemplate($code);
        } elseif ($plugin) {
            $this->resetPluginTemplates($plugin);
        } elseif ($all) {
            $this->resetAllTemplates();
        }

        $this->showSummary();

        return 0;
    }

    /**
     * Reset a single template to plugin file version
     *
     * @param string $code
     * @return void
     */
    protected function resetSingleTemplate($code)
    {
        // Find template
        $template = MailTemplate::where('code', $code)->first();

        if (!$template) {
            $this->error("Template not found: {$code}");
            $this->newLine();
            return;
        }

        if (!$template->is_custom) {
            $this->warn("Template {$code} is already using plugin version (is_custom=0)");
            $this->newLine();
            return;
        }

        // Confirm
        if (!$this->option('force')) {
            $this->warn("This will reset the following template to its plugin file version:");
            $this->line("  Code: <comment>{$code}</comment>");
            $this->line("  Subject: {$template->subject}");
            $this->newLine();

            if (!$this->confirm('Do you want to continue?', false)) {
                $this->info('Cancelled.');
                $this->newLine();
                return;
            }
        }

        // Check if plugin file exists
        if (!View::exists($code)) {
            $this->warn("Plugin file not found for {$code}.");
            $this->warn("Deleting orphaned database record.");
            $template->delete();
            $this->stats['templates']++;
            $this->line("  <info>✓</info> Deleted: {$code}");
        } else {
            // Reset by setting is_custom = 0
            $template->is_custom = 0;

            // Refill from plugin file
            try {
                $template->fillFromView($code);
                $template->save();
                $this->stats['templates']++;
                $this->line("  <info>✓</info> Reset: {$code}");
            } catch (\Exception $e) {
                $this->error("  Failed to reset {$code}: " . $e->getMessage());
            }
        }

        $this->newLine();
    }

    /**
     * Reset all templates for a specific plugin
     *
     * @param string $pluginCode
     * @return void
     */
    protected function resetPluginTemplates($pluginCode)
    {
        // Convert plugin code format: golem15.user -> golem15.user::
        $prefix = $pluginCode . '::';

        $templates = MailTemplate::where('code', 'LIKE', $prefix . '%')
            ->where('is_custom', 1)
            ->get();

        if ($templates->isEmpty()) {
            $this->warn("No custom templates found for plugin: {$pluginCode}");
            $this->newLine();
            return;
        }

        $this->info("Found {$templates->count()} custom template(s) for <comment>{$pluginCode}</comment>");
        $this->newLine();

        // Show list of templates
        foreach ($templates as $template) {
            $this->line("  - {$template->code}");
        }
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm("Reset all templates for {$pluginCode}?", false)) {
                $this->info('Cancelled.');
                $this->newLine();
                return;
            }
        }

        $this->info('Resetting templates...');
        $this->newLine();

        foreach ($templates as $template) {
            if (View::exists($template->code)) {
                try {
                    $template->is_custom = 0;
                    $template->fillFromView($template->code);
                    $template->save();
                    $this->line("  <info>✓</info> Reset: {$template->code}");
                    $this->stats['templates']++;
                } catch (\Exception $e) {
                    $this->error("  Failed to reset {$template->code}: " . $e->getMessage());
                }
            } else {
                $this->warn("  Plugin file not found: {$template->code} - deleting orphaned record");
                $template->delete();
                $this->stats['templates']++;
            }
        }

        $this->newLine();
    }

    /**
     * Reset all custom templates, layouts, and partials
     *
     * @return void
     */
    protected function resetAllTemplates()
    {
        $templates = MailTemplate::where('is_custom', 1)->get();
        $layouts = MailLayout::where('is_locked', false)->get(); // Don't reset locked system layouts
        $partials = MailPartial::where('is_custom', 1)->get();

        $total = $templates->count() + $layouts->count() + $partials->count();

        if ($total === 0) {
            $this->warn('No custom templates found to reset.');
            $this->newLine();
            return;
        }

        $this->warn("This will reset {$total} custom template(s) to plugin versions:");
        $this->line("  Templates: {$templates->count()}");
        $this->line("  Layouts: {$layouts->count()}");
        $this->line("  Partials: {$partials->count()}");
        $this->newLine();
        $this->error('WARNING: This action cannot be undone!');
        $this->newLine();

        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to continue?', false)) {
                $this->info('Cancelled.');
                $this->newLine();
                return;
            }
        }

        $this->info('Resetting templates...');
        $this->newLine();

        // Reset templates
        foreach ($templates as $template) {
            if (View::exists($template->code)) {
                try {
                    $template->is_custom = 0;
                    $template->fillFromView($template->code);
                    $template->save();
                    $this->line("  <info>✓</info> Template reset: {$template->code}");
                    $this->stats['templates']++;
                } catch (\Exception $e) {
                    $this->error("  Failed: {$template->code} - " . $e->getMessage());
                }
            } else {
                $template->delete();
                $this->warn("  <comment>✓</comment> Deleted orphaned: {$template->code}");
                $this->stats['templates']++;
            }
        }

        // Reset layouts (skip locked layouts)
        foreach ($layouts as $layout) {
            try {
                $layout->fillFromCode($layout->code);
                $layout->save();
                $this->line("  <info>✓</info> Layout reset: {$layout->code}");
                $this->stats['layouts']++;
            } catch (\Exception $e) {
                $this->warn("  Could not reset layout {$layout->code}: " . $e->getMessage());
            }
        }

        // Reset partials
        foreach ($partials as $partial) {
            try {
                $partial->fillFromCode($partial->code);
                $partial->is_custom = 0;
                $partial->save();
                $this->line("  <info>✓</info> Partial reset: {$partial->code}");
                $this->stats['partials']++;
            } catch (\Exception $e) {
                $this->warn("  Could not reset partial {$partial->code}: " . $e->getMessage());
            }
        }

        $this->newLine();
        $this->info('All custom templates reset successfully.');
        $this->newLine();
    }

    /**
     * Show reset summary
     *
     * @return void
     */
    protected function showSummary()
    {
        $this->info('Reset Summary');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->table(
            ['Type', 'Count'],
            [
                ['Templates', $this->stats['templates']],
                ['Layouts', $this->stats['layouts']],
                ['Partials', $this->stats['partials']],
                ['<info>Total</info>', '<info>' . array_sum($this->stats) . '</info>'],
            ]
        );

        $total = array_sum($this->stats);
        if ($total > 0) {
            $this->newLine();
            $this->comment('Templates have been reset to their plugin file versions');
        }

        $this->newLine();
    }
}
