<?php

namespace Golem15\Apparatus\Contracts;

use Closure;

interface Pipe
{
    public function handle(mixed $passable, Closure $next);
}
