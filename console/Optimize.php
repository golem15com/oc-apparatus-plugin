<?php
/**
 * Created by PhpStorm.
 * User: Łukasz Biały
 * URL: http://golem15.eu
 * Date: 8/13/15
 * Time: 2:17 AM
 */

namespace Golem15\Apparatus\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

/**
 * Class Optimize
 * @package Golem15\Apparatus\Console
 */
class Optimize extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'apparatus:optimize';

    /**
     * The console command description.
     */
    protected $description = 'Optimizes October Database';

    /**
     * Execute the console command.
     * @throws \ApplicationException
     */
    public function fire(): void
    {
        $this->optimizeDeferredBindings();
        $this->info('Finished');
    }

    /**
     * Changes retarded varchar(255) into integer
     *
     * @return void
     */
    public function optimizeDeferredBindings(): void
    {
        $type = \DB::connection()->getDoctrineColumn('deferred_bindings', 'slave_id')->getType()->getName();
        if ($type === 'string') {
            if ($this->confirm('Do you want to migrate deferred_bindings->slave_id to integer?', 'yes')) {
                $this->info('Migrating column to integer...');
                \Schema::table(
                    'deferred_bindings',
                    function (Blueprint $table) {
                        $table->integer('slave_id')->unsigned()->change();
                    }
                );
            }
        } else {
            $this->info('deferred_bindings already optimized, skipping...');
        }
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
