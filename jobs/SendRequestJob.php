<?php
/**
 * Copyright (c) 2018 Viamage Limited
 * All Rights Reserved
 *
 *  NOTICE:  All information contained herein is, and remains
 *  the property of Viamage Limited and its suppliers, if any.
 *  The intellectual and technical concepts contained herein
 *  are proprietary to Viamage Limited and its suppliers and are
 *  protected by trade secret or copyright law, if not specified otherwise.
 *  Dissemination of this information or reproduction of this material
 *  is strictly forbidden unless prior written permission is obtained
 *  from Viamage Limited.
 *
 */

/**
 * Created by PhpStorm.
 * User: jin
 * Date: 4/15/18
 * Time: 4:43 PM
 */

namespace Golem15\Apparatus\Jobs;

use Golem15\Apparatus\Classes\JobManager;
use Golem15\Apparatus\Classes\RequestSender;
use Golem15\Apparatus\Contracts\ApparatusQueueJob;

/**
 * Class SendRequestJob
 *
 * Sends POST requests with given data to multiple target urls. Example of Apparatus Job.
 *
 * @package Golem15\Apparatus\Jobs
 */
class SendRequestJob implements ApparatusQueueJob
{
    /**
     * @var int
     */
    public $jobId;

    /**
     * @var JobManager
     */
    public $jobManager;

    /**
     * @var array
     */
    private $targetUrls;

    /**
     * @var array
     */
    private $data;

    /**
     * @param int $id
     */
    public function assignJobId(int $id)
    {
        $this->jobId = $id;
    }

    /**
     * SendRequestJob constructor.
     *
     * We provide array with stuff to send with post and array of urls to which we want to send
     *
     * @param array $targetUrls
     * @param array $data
     * @internal param string $targetUrl
     */
    public function __construct(array $targetUrls = [], array $data = [])
    {
        $this->targetUrls = $targetUrls;
        $this->data = $data;
    }

    /**
     * Job handler. This will be done in background.
     *
     * @param JobManager $jobManager
     */
    public function handle(JobManager $jobManager)
    {
        $requestSender = new RequestSender();

        /**
         * We initialize database job. It has been assigned ID on dispatching,
         * so we pass it together with number of all elements to proceed (max_progress)
         */
        $loop = 1;
        $jobManager->startJob($this->jobId, \count($this->targetUrls));

        /**
         * We start our try-catch here, so we could safely fail job if needed.
         */
        try {

            /**
             * For each element in targetUrls we send request, increase counter and update current_progress in database
             */
            foreach ($this->targetUrls as $targetUrl) {
                $requestSender->sendPostRequest($this->data, $targetUrl);
                ++$loop;
                $jobManager->updateJobState($this->jobId, $loop);
            }

            /**
             * When we process everything properly we mark job as completed. We can pass some metadata here.
             * We can also pass metadata in update and start methods. Metadata will appear in backend as key => value table.
             */
            $jobManager->completeJob($this->jobId, ['message' => 'All finished.']);
        } catch (\Exception $e) {

            /**
             * On exception we fail job.
             */
            $jobManager->failJob($this->jobId, ['error' => $e->getMessage()]);
        }
    }
}
