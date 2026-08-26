<?php namespace Golem15\Apparatus\Console;

use Illuminate\Console\Command;
use Golem15\Apparatus\Console\Concerns\HandlesMailLocales;
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
    use HandlesMailLocales;

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
        'locales' => ['created' => 0, 'updated' => 0],
    ];

    /**
     * Layout cache for lookups
     *
     * @var array
     */
    protected $layoutCache = [];

    /**
     * Flattened filename stem => real template code. Built once per run.
     *
     * @var array|null
     */
    protected $knownCodeCache = null;

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

            if (!empty($this->getNonDefaultMailLocales())) {
                $this->info('Importing template translations...');
                $this->importTemplateLocales();
                $this->newLine();
            }

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
                $sections = $this->parseSections($content, $filename);

                // Validate required sections
                if (!isset($sections['settings']['name']) || $sections['settings']['name'] === '') {
                    $this->warn("  Skipped {$filename}: Missing 'name' in settings");
                    continue;
                }

                // Find or create layout
                $layout = MailLayout::firstOrNew(['code' => $code]);
                $isNew = !$layout->exists;

                // Update fields
                $layout->name = $this->unescapeIniValue($sections['settings']['name']);
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
                $sections = $this->parseSections($content, $filename);

                if (!isset($sections['settings']['name']) || $sections['settings']['name'] === '') {
                    $this->warn("  Skipped {$filename}: Missing 'name' in settings");
                    continue;
                }

                $partial = MailPartial::firstOrNew(['code' => $code]);
                $isNew = !$partial->exists;

                $partial->name = $this->unescapeIniValue($sections['settings']['name']);
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
                $sections = $this->parseSections($content, $filename);

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
                $template->subject = $this->unescapeIniValue($sections['settings']['subject']);
                $template->description = $this->unescapeIniValue($sections['settings']['description']);
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
     * Import per-locale template overrides from templates/locales/<code>/.
     *
     * Translations live in winter_translate_attributes, not in the mail template's own
     * columns, so these have to go through the model: setAttributeTranslated() followed
     * by save() lets the Translate behaviour persist them. Writing the table directly
     * would bypass the behaviour and leave a stale cache behind.
     *
     * @return void
     */
    protected function importTemplateLocales()
    {
        $anyFound = false;

        foreach ($this->getNonDefaultMailLocales() as $locale) {
            $directory = $this->localeDirectory('templates', $locale);

            if (!File::exists($directory)) {
                continue;
            }

            foreach (File::files($directory) as $file) {
                if ($file->getExtension() !== 'htm') {
                    continue;
                }

                $anyFound = true;
                $filename = $file->getFilename();
                $code = $this->getCodeFromFilename($filename);

                try {
                    $sections = $this->parseSections(File::get($file->getPathname()), "{$locale}/{$filename}");

                    if (!isset($sections['settings']['subject']) || $sections['settings']['subject'] === '') {
                        $this->warn("  Skipped {$locale}/{$filename}: Missing 'subject' in settings");
                        continue;
                    }

                    $template = MailTemplate::where('code', $code)->first();

                    if (!$template) {
                        $this->warn("  Skipped {$locale}/{$filename}: No template with code {$code}");
                        continue;
                    }

                    if (!$this->isMailModelTranslatable($template)) {
                        $this->warn("  Skipped {$locale}/{$filename}: Mail templates are not translatable on this site");
                        return;
                    }

                    $existing = (array) $template->getTranslateAttributes($locale);
                    $isNew = empty(array_filter($existing, function ($value) {
                        return trim((string) $value) !== '';
                    }));

                    $values = [
                        'subject'      => $this->unescapeIniValue($sections['settings']['subject']),
                        'description'  => $this->unescapeIniValue($sections['settings']['description'] ?? ''),
                        'content_text' => $sections['text'] ?? '',
                        'content_html' => $sections['html'] ?? '',
                    ];

                    $template->translateContext($locale);

                    foreach ($values as $attribute => $value) {
                        $template->setAttributeTranslated($attribute, $value, $locale);
                    }

                    if (!$this->option('dry-run')) {
                        $template->save();
                        $this->forgetTranslationCache($template, $locale);
                    }

                    if ($isNew) {
                        $this->stats['locales']['created']++;
                        $this->line("  <info>✓ Created:</info> {$locale}/{$filename} <comment>(code: {$code})</comment>");
                    } else {
                        $this->stats['locales']['updated']++;
                        $this->line("  <comment>✓ Updated:</comment> {$locale}/{$filename} <comment>(code: {$code})</comment>");
                    }

                } catch (\Exception $e) {
                    $this->error("  Failed to import {$locale}/{$filename}: " . $e->getMessage());
                }
            }
        }

        if (!$anyFound) {
            $this->line('  <comment>No locale files found.</comment>');
        }
    }

    /**
     * Convert filename back to template code
     *
     * Export flattens the :: separator to a dot (author.plugin::mail.activate becomes
     * author.plugin.mail.activate.htm), which loses where the separator was. Guessing
     * that it always sits after the first two segments is wrong for module-scoped
     * codes: backend::mail.invite would come back as backend.mail::invite and silently
     * create a duplicate row instead of updating the real template.
     *
     * So resolve against the codes that actually exist first -- every registered and
     * stored template, flattened the same way export flattens them -- and only fall
     * back to the author.plugin heuristic for a genuinely new file.
     *
     * Examples:
     *   golem15.user.mail.activate.htm -> golem15.user::mail.activate
     *   backend.mail.invite.htm        -> backend::mail.invite
     *   default.htm                    -> default (no ::)
     *
     * @param string $filename
     * @return string
     */
    protected function getCodeFromFilename($filename)
    {
        $code = preg_replace('/\\.htm$/', '', $filename);

        $known = $this->getKnownTemplateCodes();

        if (isset($known[$code])) {
            return $known[$code];
        }

        // Unknown file: assume the plugin convention, author.plugin::the.rest
        $parts = explode('.', $code);

        if (count($parts) >= 3) {
            return $parts[0] . '.' . $parts[1] . '::' . implode('.', array_slice($parts, 2));
        }

        // Simple code without a namespace (e.g. "default", "system")
        return $code;
    }

    /**
     * Map of flattened filename stem => real template code, for every code the system
     * knows about (registered by a plugin, or already stored in the database).
     *
     * @return array
     */
    protected function getKnownTemplateCodes()
    {
        if ($this->knownCodeCache !== null) {
            return $this->knownCodeCache;
        }

        $codes = MailTemplate::pluck('code')->all();

        try {
            $codes = array_merge(
                $codes,
                array_keys(\System\Classes\MailManager::instance()->listRegisteredTemplates())
            );
        } catch (\Exception $e) {
            // Registration is unavailable in some contexts; the database list still applies.
        }

        $this->knownCodeCache = [];

        foreach (array_unique($codes) as $code) {
            $this->knownCodeCache[str_replace('::', '.', $code)] = $code;
        }

        return $this->knownCodeCache;
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
     * Parse a mail file, refusing to silently drop content.
     *
     * MailParser splits on every line starting with two or more "=" but only reads the
     * first three sections, so a rule like "====" written at column 0 inside an HTML
     * body truncates the template with no warning at all. Detect that and fail loudly
     * -- the import runs in a transaction, so throwing rolls the whole run back rather
     * than half-writing a mangled template.
     *
     * @param string $content
     * @param string $filename
     * @return array
     * @throws \Exception
     */
    protected function parseSections($content, $filename)
    {
        $sectionCount = count(preg_split('/^={2,}\s*/m', $content, -1));

        if ($sectionCount > 3) {
            throw new \Exception(sprintf(
                '%s has %d sections but the format allows 3. A line starting with "==" '
                . 'inside the body would be silently truncated - indent it or remove it.',
                $filename,
                $sectionCount
            ));
        }

        return MailParser::parse($content);
    }

    /**
     * Unescape INI value (reverse of export escaping)
     *
     * @param string $value
     * @return string
     */
    protected function unescapeIniValue($value)
    {
        if ($value === null) {
            return '';
        }

        // parse_ini_string() has already turned \" into " and collapsed each \\ pair,
        // so what remains are this exporter's own markers.
        //
        // A single pass is required. Replacing \\ and then \n sequentially corrupts a
        // value such as "C:\new": the first pass emits a backslash that the second pass
        // then reads as the start of a newline escape.
        return preg_replace_callback('/\\\\(.)/s', function ($matches) {
            switch ($matches[1]) {
                case 'n':
                    return "\n";
                case 'r':
                    return "\r";
                case '\\':
                    return '\\';
                default:
                    return $matches[0];
            }
        }, $value);
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
                [
                    'Locale variants',
                    $this->stats['locales']['created'],
                    $this->stats['locales']['updated'],
                    $this->stats['locales']['created'] + $this->stats['locales']['updated']
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
