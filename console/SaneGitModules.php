<?php

namespace Golem15\Apparatus\Console;

use Illuminate\Console\Command;

/**
 * Class SaneModules
 * @package Golem15\Apparatus\Console
 */
class SaneGitModules extends Command
{
    /**
     * The console command name.
     */
    protected $signature = 'g15:sane-git {--insane}';

    /**
     * The console command description.
     */
    protected $description = 'For gitmodule based repo sanity';

    /**
     * Execute the console command.
     * @throws \ApplicationException
     */
    public function handle()
    {
        exec('git status|wc -l', $countBefore);
        $this->info('git status count before: ' . $countBefore[0]);

        if ($this->option('insane')) {
            $command = "cd modules; find . -maxdepth 1 -type d \( ! -name . \) -exec bash -c \"cd '{}' && pwd && git ls-files -z \${pwd} | xargs -0 git update-index --no-skip-worktree\" \;";
            exec($command);
            $this->removeExcludes();
        } else {
            $command = "cd modules; find . -maxdepth 1 -type d \( ! -name . \) -exec bash -c \"cd '{}' && pwd && git ls-files -z \${pwd} | xargs -0 git update-index --skip-worktree\" \;";
            exec($command);
            $this->addExcludes();
        }

        exec('git status|wc -l', $countAfter);
        $this->info('git status count after: ' . $countAfter[0]);

        $this->info('Finished');
    }

    /**
     * Resolve the actual .git directory path.
     * Handles both standalone repos (.git is a directory) and submodules (.git is a file with gitdir pointer).
     */
    private function resolveGitDir(): string
    {
        $output = [];
        exec('git rev-parse --git-dir 2>/dev/null', $output, $exitCode);

        if ($exitCode === 0 && !empty($output[0])) {
            $gitDir = $output[0];
            // Make absolute if relative
            if (!str_starts_with($gitDir, '/')) {
                $gitDir = base_path($gitDir);
            }
            return rtrim($gitDir, '/');
        }

        // Fallback to default
        return base_path('.git');
    }

    private function addExcludes(): void
    {
        $gitDir = $this->resolveGitDir();
        $excludePath = $gitDir . '/info/exclude';

        // Ensure the info/ directory exists
        $infoDir = dirname($excludePath);
        if (!is_dir($infoDir)) {
            mkdir($infoDir, 0755, true);
        }

        $existing = file_exists($excludePath) ? file_get_contents($excludePath) : '';

        // Remove old managed section BEFORE querying, so --exclude-standard
        // doesn't hide previously excluded files from the listing
        $cleaned = preg_replace('/\n?# BEGIN g15:sane-git\n.*?# END g15:sane-git\n?/s', '', $existing);
        file_put_contents($excludePath, $cleaned);

        exec('git ls-files --others --exclude-standard -- modules/', $untracked);
        if (empty($untracked)) {
            $this->info('No untracked files in modules/ to exclude.');
            return;
        }

        $paths = array_unique($untracked);
        $section = "\n# BEGIN g15:sane-git\n" . implode("\n", $paths) . "\n# END g15:sane-git\n";

        file_put_contents($excludePath, rtrim($cleaned) . $section);
        $this->info('Added ' . count($paths) . ' untracked module paths to ' . $excludePath);
    }

    private function removeExcludes(): void
    {
        $gitDir = $this->resolveGitDir();
        $excludePath = $gitDir . '/info/exclude';

        if (!file_exists($excludePath)) {
            return;
        }
        $content = file_get_contents($excludePath);
        $cleaned = preg_replace('/\n?# BEGIN g15:sane-git\n.*?# END g15:sane-git\n?/s', '', $content);
        file_put_contents($excludePath, $cleaned);
        $this->info('Removed g15:sane-git managed excludes from ' . $excludePath);
    }

    /**
     * Get the console command arguments.
     */
    protected function getArguments(): array
    {
        return [];
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [];
    }
}
