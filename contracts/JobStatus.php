<?php
/**
 * Created by Keios Solutions
 * User: Jakub Zych
 * Date: 5/31/16
 * Time: 9:42 PM
 */

namespace Keios\Apparatus\Contracts;

/**
 * Interface JobStatus
 * @package Keios\Apparatus\Contracts
 */
interface JobStatus
{
    const IN_QUEUE = 0;
    const IN_PROGRESS = 1;
    const COMPLETE = 2;
    const ERROR = 3;
    const STOPPED = 4;
}