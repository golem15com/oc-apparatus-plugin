<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 1/3/18
 * Time: 10:17 AM
 */

namespace Golem15\Apparatus\Contracts;

interface ApparatusQueueJob
{
    public function assignJobId(int $id);
}
