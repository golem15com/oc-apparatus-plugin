<?php

namespace Golem15\Apparatus\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class SaneModules
 * @package PixelPixel\Mplibrary\Console
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

    private function addExcludes(): void
    {
        exec('git ls-files --others --exclude-standard -- modules/', $untracked);
        if (empty($untracked)) {
            $this->info('No untracked files in modules/ to exclude.');
            return;
        }

        $excludePath = base_path('.git/info/exclude');
        $existing = file_exists($excludePath) ? file_get_contents($excludePath) : '';

        // Remove old managed section if present (idempotent re-run)
        $existing = preg_replace('/\n?# BEGIN g15:sane-git\n.*?# END g15:sane-git\n?/s', '', $existing);

        $paths = array_unique($untracked);
        $section = "\n# BEGIN g15:sane-git\n" . implode("\n", $paths) . "\n# END g15:sane-git\n";

        file_put_contents($excludePath, rtrim($existing) . $section);
        $this->info('Added ' . count($paths) . ' untracked module paths to .git/info/exclude');
    }

    private function removeExcludes(): void
    {
        $excludePath = base_path('.git/info/exclude');
        if (!file_exists($excludePath)) {
            return;
        }
        $content = file_get_contents($excludePath);
        $cleaned = preg_replace('/\n?# BEGIN g15:sane-git\n.*?# END g15:sane-git\n?/s', '', $content);
        file_put_contents($excludePath, $cleaned);
        $this->info('Removed g15:sane-git managed excludes from .git/info/exclude');
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
