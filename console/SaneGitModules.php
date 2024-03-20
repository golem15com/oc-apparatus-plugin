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
        if($this->option('insane')) {
            $command = "cd modules; find . -maxdepth 1 -type d \( ! -name . \) -exec bash -c \"cd '{}' && pwd && git ls-files -z \${pwd} | xargs -0 git update-index --no-skip-worktree\" \;";
        } else {
            $command = "cd modules; find . -maxdepth 1 -type d \( ! -name . \) -exec bash -c \"cd '{}' && pwd && git ls-files -z \${pwd} | xargs -0 git update-index --skip-worktree\" \;";
        }
        exec($command);
        exec('git status|wc -l', $countAfter);
        $this->info('git status count after: ' . $countAfter[0]);

        $this->info('Finished');
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
