<?php namespace Golem15\Apparatus\Classes;

use Illuminate\Contracts\Container\Container;
use Golem15\Apparatus\Contracts\NeedsDependencies;
use October\Rain\Exception\ApplicationException;

/**
 * Class DependencyInjector
 *
 * @package Golem15\Apparatus\Classes
 */
class DependencyInjector
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * DependencyInjector constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param object $object
     * @throws ApplicationException
     */
    public function injectDependencies($object)
    {
        if (!$object instanceof NeedsDependencies) {
            return;
        }

        foreach (get_class_methods($object) as $method) {
            if (strpos($method, 'inject') === 0) {
                try {
                    $this->container->call([$object, $method]);
                } catch(\Exception $e){
                    $msg = $e->getMessage() . ' at class: '. \get_class($object);
                    throw new ApplicationException($msg);
                }
            }
        }
    }

}
