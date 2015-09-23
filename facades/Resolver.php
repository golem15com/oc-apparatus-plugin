<?php namespace Keios\Apparatus\Facades;


use October\Rain\Support\Facade;

/**
 * Class Resolver
 *
 * @package Keios\Apparatus\Facades
 */
class Resolver extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'apparatus.route.resolver';
    }

}