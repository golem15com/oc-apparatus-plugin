<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 9/18/17
 * Time: 5:36 PM
 */

namespace Golem15\Apparatus\Classes;

use October\Rain\Database\Connectors\ConnectionFactory;
use October\Rain\Foundation\Application;

class DatabaseManager extends \Illuminate\Database\DatabaseManager
{
    /**
     * Create a new database manager instance.
     * Compatible with 5.1 code
     *
     * @param \Illuminate\Foundation\Application|Application                      $app
     * @param \Illuminate\Database\Connectors\ConnectionFactory|ConnectionFactory $factory
     */
    public function __construct(Application $app, ConnectionFactory $factory)
    {
        parent::__construct($app, $factory);
    }

}
