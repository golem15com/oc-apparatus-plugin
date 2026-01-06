<?php namespace Golem15\Apparatus\Console;

use Illuminate\Console\Command;
use System\Models\MailTemplate;
use System\Models\MailLayout;
use System\Models\MailPartial;
use File;

/**
 * Mail Export Command
 *
 * Exports all mail templates, layouts, and partials from database to mail_templates/ folder.
 * This allows version control of email templates and customization of plugin emails.
 *
 * @package Golem15\Apparatus\Console
 * @author Golem15
 */
class MailExportCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'apparatus:mail-export {--force : Overwrite existing files without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export all mail templates, layouts, and partials from database to mail_templates/ folder';

    /**
     * Base path for mail templates
     *
     * @var string
     */
    protected $basePath;

    /**
     * Export statistics
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
        $this->basePath = base_path('mail_templates');

        $this->info('Mail Template Export');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        // Step 1: Check/create directory structure
        if (!$this->ensureDirectoryStructure()) {
            return 1;
        }

        // Step 2: Export templates
        $this->info('Exporting mail templates...');
        $this->exportTemplates();

        // Step 3: Export layouts
        $this->info('Exporting mail layouts...');
        $this->exportLayouts();

        // Step 4: Export partials
        $this->info('Exporting mail partials...');
        $this->exportPartials();

        // Step 5: Show summary
        $this->showSummary();

        return 0;
    }

    /**
     * Ensure directory structure exists
     *
     * @return bool
     */
    protected function ensureDirectoryStructure()
    {
        $directories = [
            $this->basePath . '/templates',
            $this->basePath . '/layouts',
            $this->basePath . '/partials',
        ];

        try {
            foreach ($directories as $dir) {
                if (!File::exists($dir)) {
                    File::makeDirectory($dir, 0755, true);
                    $this->comment("Created directory: " . str_replace(base_path(), '', $dir));
                }
            }
            return true;
        } catch (\Exception $e) {
            $this->error('Cannot create mail_templates/ directory: ' . $e->getMessage());
            $this->comment('Try running: chmod 755 ' . base_path());
            return false;
        }
    }

    /**
     * Export templates from database to files
     *
     * @return void
     */
    protected function exportTemplates()
    {
        // Get all templates from database
        $templates = MailTemplate::with('layout')->get();

        if ($templates->isEmpty()) {
            $this->warn('No templates found in database.');
            return;
        }

        foreach ($templates as $template) {
            $filename = $this->sanitizeFilename($template->code) . '.htm';
            $filepath = $this->basePath . '/templates/' . $filename;

            // Check if file exists
            if (File::exists($filepath) && !$this->option('force')) {
                if (!$this->confirm("  File {$filename} exists. Overwrite?", false)) {
                    $this->warn("  Skipped: {$filename}");
                    continue;
                }
            }

            // Build template content in three-section format
            $content = $this->buildTemplateContent($template);

            // Write to file atomically
            $this->writeFileAtomically($filepath, $content);

            $this->stats['templates']++;
            $this->line("  <info>✓</info> Exported: {$filename}");
        }
    }

    /**
     * Export layouts from database to files
     *
     * @return void
     */
    protected function exportLayouts()
    {
        $layouts = MailLayout::all();

        if ($layouts->isEmpty()) {
            $this->warn('No layouts found in database.');
            return;
        }

        foreach ($layouts as $layout) {
            $filename = $this->sanitizeFilename($layout->code) . '.htm';
            $filepath = $this->basePath . '/layouts/' . $filename;

            if (File::exists($filepath) && !$this->option('force')) {
                if (!$this->confirm("  File {$filename} exists. Overwrite?", false)) {
                    $this->warn("  Skipped: {$filename}");
                    continue;
                }
            }

            $content = $this->buildLayoutContent($layout);
            $this->writeFileAtomically($filepath, $content);

            $this->stats['layouts']++;
            $this->line("  <info>✓</info> Exported: {$filename}");
        }
    }

    /**
     * Export partials from database to files
     *
     * @return void
     */
    protected function exportPartials()
    {
        $partials = MailPartial::all();

        if ($partials->isEmpty()) {
            $this->warn('No partials found in database.');
            return;
        }

        foreach ($partials as $partial) {
            $filename = $this->sanitizeFilename($partial->code) . '.htm';
            $filepath = $this->basePath . '/partials/' . $filename;

            if (File::exists($filepath) && !$this->option('force')) {
                if (!$this->confirm("  File {$filename} exists. Overwrite?", false)) {
                    $this->warn("  Skipped: {$filename}");
                    continue;
                }
            }

            $content = $this->buildPartialContent($partial);
            $this->writeFileAtomically($filepath, $content);

            $this->stats['partials']++;
            $this->line("  <info>✓</info> Exported: {$filename}");
        }
    }

    /**
     * Build template content in three-section INI format
     *
     * @param MailTemplate $template
     * @return string
     */
    protected function buildTemplateContent($template)
    {
        $settings = [];
        $settings[] = 'subject = "' . $this->escapeIniValue($template->subject) . '"';

        // Use default description if empty (Winter CMS requires non-empty descriptions)
        $description = !empty($template->description) ? $template->description : 'Email template';
        $settings[] = 'description = "' . $this->escapeIniValue($description) . '"';

        // Add layout if not default
        if ($template->layout && $template->layout->code !== 'default') {
            $settings[] = 'layout = "' . $template->layout->code . '"';
        }

        $settingsSection = implode("\n", $settings);
        $textSection = $template->content_text ?? '';
        $htmlSection = $template->content_html ?? '';

        return $settingsSection . "\n==\n" . $textSection . "\n==\n" . $htmlSection;
    }

    /**
     * Build layout content in three-section format
     *
     * @param MailLayout $layout
     * @return string
     */
    protected function buildLayoutContent($layout)
    {
        $settings = 'name = "' . $this->escapeIniValue($layout->name) . '"';

        // Add CSS if present
        if (!empty($layout->content_css)) {
            $settings .= "\ncss = \"" . $this->escapeIniValue($layout->content_css) . "\"";
        }

        $textSection = $layout->content_text ?? '';
        $htmlSection = $layout->content_html ?? '';

        return $settings . "\n==\n" . $textSection . "\n==\n" . $htmlSection;
    }

    /**
     * Build partial content in three-section format
     *
     * @param MailPartial $partial
     * @return string
     */
    protected function buildPartialContent($partial)
    {
        $settings = 'name = "' . $this->escapeIniValue($partial->name) . '"';
        $textSection = $partial->content_text ?? '';
        $htmlSection = $partial->content_html ?? '';

        return $settings . "\n==\n" . $textSection . "\n==\n" . $htmlSection;
    }

    /**
     * Sanitize code into safe filename
     * Replaces :: with . for filesystem safety
     *
     * @param string $code
     * @return string
     */
    protected function sanitizeFilename($code)
    {
        // Replace :: with . for filesystem safety
        // golem15.user::activate -> golem15.user.activate
        return str_replace('::', '.', $code);
    }

    /**
     * Escape INI value (double quotes and newlines)
     *
     * @param string|null $value
     * @return string
     */
    protected function escapeIniValue($value)
    {
        if ($value === null) {
            return '';
        }

        // Escape double quotes
        $escaped = str_replace('"', '\\"', $value);

        // Escape newlines for INI format
        $escaped = str_replace("\n", "\\n", $escaped);
        $escaped = str_replace("\r", "\\r", $escaped);

        return $escaped;
    }

    /**
     * Write file atomically to prevent corruption
     *
     * @param string $filepath
     * @param string $content
     * @return void
     */
    protected function writeFileAtomically($filepath, $content)
    {
        $tempFile = $filepath . '.tmp';
        File::put($tempFile, $content);
        File::move($tempFile, $filepath, true);
    }

    /**
     * Show export summary
     *
     * @return void
     */
    protected function showSummary()
    {
        $this->newLine();
        $this->info('Export Summary');
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

        $this->newLine();
        $this->info("Files exported to: " . str_replace(base_path(), '', $this->basePath));
        $this->newLine();
        $this->comment('Next steps:');
        $this->line('  1. Edit templates in mail_templates/ folder');
        $this->line('  2. Run: <comment>php artisan apparatus:mail-import</comment>');
        $this->line('  3. Test emails to verify changes');
        $this->newLine();
    }
}
