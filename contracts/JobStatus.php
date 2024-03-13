<?php
/**
 * Created by Golem15 Solutions
 * User: Jakub Zych
 * Date: 5/31/16
 * Time: 9:42 PM
 */

namespace Golem15\Apparatus\Contracts;

/**
 * Interface JobStatus
 * @package Golem15\Apparatus\Contracts
 */
interface JobStatus
{
    const IN_QUEUE = 0;
    const IN_PROGRESS = 1;
    const COMPLETE = 2;
    const ERROR = 3;
    const STOPPED = 4;
}
