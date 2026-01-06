<?php namespace Golem15\Apparatus\Console;

use Illuminate\Console\Command;
use System\Models\MailTemplate;
use System\Models\MailLayout;
use System\Models\MailPartial;
use Winter\Storm\Mail\MailParser;
use File;
use DB;

/**
 * Mail Import Command
 *
 * Imports mail templates, layouts, and partials from mail_templates/ folder to database.
 * Sets is_custom=1 to mark templates as database overrides.
 *
 * @package Golem15\Apparatus\Console
 * @author Golem15
 */
class MailImportCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'apparatus:mail-import {--dry-run : Show what would be imported without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import mail templates, layouts, and partials from mail_templates/ folder to database';

    /**
     * Base path for mail templates
     *
     * @var string
     */
    protected $basePath;

    /**
     * Import statistics
     *
     * @var array
     */
    protected $stats = [
        'templates' => ['created' => 0, 'updated' => 0],
        'layouts' => ['created' => 0, 'updated' => 0],
        'partials' => ['created' => 0, 'updated' => 0],
    ];

    /**
     * Layout cache for lookups
     *
     * @var array
     */
    protected $layoutCache = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->basePath = base_path('mail_templates');

        $this->info('Mail Template Import');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE - No database changes will be made');
            $this->newLine();
        }

        // Step 1: Validate directory structure
        if (!$this->validateDirectoryStructure()) {
            return 1;
        }

        // Step 2: Import in correct order (layouts first, then templates)
        DB::beginTransaction();

        try {
            $this->info('Importing mail layouts...');
            $this->importLayouts();
            $this->newLine();

            $this->info('Importing mail partials...');
            $this->importPartials();
            $this->newLine();

            $this->info('Importing mail templates...');
            $this->importTemplates();
            $this->newLine();

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('DRY RUN: No changes were made to the database.');
            } else {
                DB::commit();
                $this->info('All templates imported successfully.');
            }

            $this->showSummary();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Import failed: ' . $e->getMessage());
            $this->newLine();
            $this->comment('Database rolled back - no changes were made');
            $this->newLine();
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    /**
     * Validate directory structure exists
     *
     * @return bool
     */
    protected function validateDirectoryStructure()
    {
        $requiredDirs = [
            $this->basePath . '/templates',
            $this->basePath . '/layouts',
            $this->basePath . '/partials',
        ];

        $missing = [];
        foreach ($requiredDirs as $dir) {
            if (!File::exists($dir)) {
                $missing[] = str_replace(base_path(), '', $dir);
            }
        }

        if (!empty($missing)) {
            $this->error('Required directories not found:');
            foreach ($missing as $dir) {
                $this->line("  - {$dir}");
            }
            $this->newLine();
            $this->comment('Run: <comment>php artisan apparatus:mail-export</comment> first');
            return false;
        }

        return true;
    }

    /**
     * Import layouts from files to database
     *
     * @return void
     */
    protected function importLayouts()
    {
        $layoutFiles = File::files($this->basePath . '/layouts');

        if (empty($layoutFiles)) {
            $this->warn('  No layout files found.');
            return;
        }

        foreach ($layoutFiles as $file) {
            if ($file->getExtension() !== 'htm') {
                continue;
            }

            $filename = $file->getFilename();
            $code = $this->getCodeFromFilename($filename);

            try {
                $content = File::get($file->getPathname());
                $sections = MailParser::parse($content);

                // Validate required sections
                if (!isset($sections['settings']['name']) || $sections['settings']['name'] === '') {
                    $this->warn("  Skipped {$filename}: Missing 'name' in settings");
                    continue;
                }

                // Find or create layout
                $layout = MailLayout::firstOrNew(['code' => $code]);
                $isNew = !$layout->exists;

                // Update fields
                $layout->name = $sections['settings']['name'];
                $layout->content_html = $sections['html'] ?? '';
                $layout->content_text = $sections['text'] ?? '';

                // Handle CSS if present
                if (!empty($sections['settings']['css'])) {
                    $layout->content_css = $this->unescapeIniValue($sections['settings']['css']);
                }

                // Don't override is_locked for existing locked layouts
                if ($isNew) {
                    $layout->is_locked = false;
                }

                if (!$this->option('dry-run')) {
                    $layout->save();
                    // Clear layout cache to ensure fresh lookups
                    $this->layoutCache = [];
                }

                if ($isNew) {
                    $this->stats['layouts']['created']++;
                    $this->line("  <info>✓ Created:</info> {$filename} <comment>(code: {$code})</comment>");
                } else {
                    $this->stats['layouts']['updated']++;
                    $this->line("  <comment>✓ Updated:</comment> {$filename} <comment>(code: {$code})</comment>");
                }

            } catch (\Exception $e) {
                $this->error("  Failed to import {$filename}: " . $e->getMessage());
            }
        }
    }

    /**
     * Import partials from files to database
     *
     * @return void
     */
    protected function importPartials()
    {
        $partialFiles = File::files($this->basePath . '/partials');

        if (empty($partialFiles)) {
            $this->warn('  No partial files found.');
            return;
        }

        foreach ($partialFiles as $file) {
            if ($file->getExtension() !== 'htm') {
                continue;
            }

            $filename = $file->getFilename();
            $code = $this->getCodeFromFilename($filename);

            try {
                $content = File::get($file->getPathname());
                $sections = MailParser::parse($content);

                if (!isset($sections['settings']['name']) || $sections['settings']['name'] === '') {
                    $this->warn("  Skipped {$filename}: Missing 'name' in settings");
                    continue;
                }

                $partial = MailPartial::firstOrNew(['code' => $code]);
                $isNew = !$partial->exists;

                $partial->name = $sections['settings']['name'];
                $partial->content_html = $sections['html'] ?? '';
                $partial->content_text = $sections['text'] ?? '';
                $partial->is_custom = 1;

                if (!$this->option('dry-run')) {
                    $partial->save();
                }

                if ($isNew) {
                    $this->stats['partials']['created']++;
                    $this->line("  <info>✓ Created:</info> {$filename} <comment>(code: {$code})</comment>");
                } else {
                    $this->stats['partials']['updated']++;
                    $this->line("  <comment>✓ Updated:</comment> {$filename} <comment>(code: {$code})</comment>");
                }

            } catch (\Exception $e) {
                $this->error("  Failed to import {$filename}: " . $e->getMessage());
            }
        }
    }

    /**
     * Import templates from files to database
     *
     * @return void
     */
    protected function importTemplates()
    {
        $templateFiles = File::files($this->basePath . '/templates');

        if (empty($templateFiles)) {
            $this->warn('  No template files found.');
            return;
        }

        foreach ($templateFiles as $file) {
            if ($file->getExtension() !== 'htm') {
                continue;
            }

            $filename = $file->getFilename();
            $code = $this->getCodeFromFilename($filename);

            try {
                $content = File::get($file->getPathname());
                $sections = MailParser::parse($content);

                // Validate required settings
                if (!isset($sections['settings']['subject']) || $sections['settings']['subject'] === '') {
                    $this->warn("  Skipped {$filename}: Missing 'subject' in settings");
                    continue;
                }

                // Description can be empty, but must exist as a key
                if (!isset($sections['settings']['description'])) {
                    $this->warn("  Skipped {$filename}: Missing 'description' in settings");
                    continue;
                }

                $template = MailTemplate::firstOrNew(['code' => $code]);
                $isNew = !$template->exists;

                // Update fields
                $template->subject = $sections['settings']['subject'];
                $template->description = $sections['settings']['description'];
                $template->content_html = $sections['html'] ?? '';
                $template->content_text = $sections['text'] ?? '';
                $template->is_custom = 1;

                // Resolve layout_id from layout code
                $layoutCode = $sections['settings']['layout'] ?? 'default';
                $layoutId = $this->getLayoutId($layoutCode);

                if (!$layoutId) {
                    $availableLayouts = MailLayout::pluck('code')->toArray();
                    $this->warn("  Warning: Layout '{$layoutCode}' not found for {$filename}");
                    $this->warn("  Available layouts: " . implode(', ', $availableLayouts));
                    $this->warn("  Falling back to 'default' layout");
                    $layoutId = $this->getLayoutId('default');
                }

                $template->layout_id = $layoutId;

                if (!$this->option('dry-run')) {
                    $template->save();
                }

                if ($isNew) {
                    $this->stats['templates']['created']++;
                    $this->line("  <info>✓ Created:</info> {$filename} <comment>(code: {$code}, layout: {$layoutCode})</comment>");
                } else {
                    $this->stats['templates']['updated']++;
                    $this->line("  <comment>✓ Updated:</comment> {$filename} <comment>(code: {$code}, layout: {$layoutCode})</comment>");
                }

            } catch (\Exception $e) {
                $this->error("  Failed to import {$filename}: " . $e->getMessage());
            }
        }
    }

    /**
     * Convert filename back to template code
     *
     * Winter CMS plugin codes follow the pattern: author.plugin::template.name
     * When exported, the :: is replaced with a dot: author.plugin.template.name.htm
     *
     * Examples:
     *   golem15.user.mail.activate.htm -> golem15.user::mail.activate
     *   backend.mail.invite.htm -> backend::mail.invite
     *   default.htm -> default (no ::)
     *
     * @param string $filename
     * @return string
     */
    protected function getCodeFromFilename($filename)
    {
        $code = str_replace('.htm', '', $filename);

        // Check if this is a plugin template (has at least 3 parts: author.plugin.template)
        $parts = explode('.', $code);
        $partCount = count($parts);

        if ($partCount >= 3) {
            // For plugin templates, the :: separator goes after author.plugin (first two parts)
            // golem15.user.mail.activate -> golem15.user::mail.activate
            $author = $parts[0];
            $plugin = $parts[1];
            $templatePath = implode('.', array_slice($parts, 2));

            return $author . '.' . $plugin . '::' . $templatePath;
        }

        // Simple code without plugin namespace (e.g., "default", "system")
        return $code;
    }

    /**
     * Get layout ID from layout code (with caching)
     *
     * @param string $layoutCode
     * @return int|null
     */
    protected function getLayoutId($layoutCode)
    {
        if (!isset($this->layoutCache[$layoutCode])) {
            $this->layoutCache[$layoutCode] = MailLayout::where('code', $layoutCode)->value('id');
        }

        return $this->layoutCache[$layoutCode];
    }

    /**
     * Unescape INI value (reverse of export escaping)
     *
     * @param string $value
     * @return string
     */
    protected function unescapeIniValue($value)
    {
        $unescaped = str_replace('\\"', '"', $value);
        $unescaped = str_replace('\\n', "\n", $unescaped);
        $unescaped = str_replace('\\r', "\r", $unescaped);
        return $unescaped;
    }

    /**
     * Show import summary
     *
     * @return void
     */
    protected function showSummary()
    {
        $this->info('Import Summary');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->table(
            ['Type', 'Created', 'Updated', 'Total'],
            [
                [
                    'Templates',
                    $this->stats['templates']['created'],
                    $this->stats['templates']['updated'],
                    $this->stats['templates']['created'] + $this->stats['templates']['updated']
                ],
                [
                    'Layouts',
                    $this->stats['layouts']['created'],
                    $this->stats['layouts']['updated'],
                    $this->stats['layouts']['created'] + $this->stats['layouts']['updated']
                ],
                [
                    'Partials',
                    $this->stats['partials']['created'],
                    $this->stats['partials']['updated'],
                    $this->stats['partials']['created'] + $this->stats['partials']['updated']
                ],
            ]
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('DRY RUN MODE - No database changes were made');
        } else {
            $this->newLine();
            $this->info('All templates marked as custom (is_custom=1)');
            $this->newLine();
            $this->comment('Next steps:');
            $this->line('  1. Test emails to verify templates render correctly');
            $this->line('  2. Check backend: <comment>Settings → Mail → Mail templates</comment>');
        }

        $this->newLine();
    }
}
