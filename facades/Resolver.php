<?php namespace Golem15\Apparatus\Facades;

use October\Rain\Support\Facade;

/**
 * Class Resolver
 *
 * @package Golem15\Apparatus\Facades
 */
class Resolver extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'apparatus.route.resolver';
    }

}
