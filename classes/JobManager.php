<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 1/3/18
 * Time: 9:55 AM
 */

namespace Keios\Apparatus\Classes;

use Keios\Apparatus\Contracts\ApparatusQueueJob;
use Keios\Apparatus\Contracts\JobStatus;
use Keios\Apparatus\Models\Job;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\Connection;

/**
 * Class JobManager
 * @package Keios\Apparatus\Classes
 */
class JobManager
{
    const JOB_TABLE = 'keios_apparatus_jobs';

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * JobManager constructor.
     *
     * @param Connection $db
     * @param Queue      $queue
     */
    public function __construct(Connection $db, Queue $queue)
    {
        $this->db = $db;
        $this->queue = $queue;
    }

    /**
     * @param ApparatusQueueJob $job
     * @param string            $label
     * @param array             $parameters
     * @return int
     * @internal param int $type
     */
    public function dispatch(ApparatusQueueJob $job, string $label, array $parameters = [])
    {
        $isAdmin = false;
        $userId = null;
        $user = \Auth::getUser();
        if ($user) {
            $userId = $user->id;
            $isAdmin = false;
        }
        $user = \BackendAuth::getUser();
        if ($user) {
            $userId = $user->id;
            $isAdmin = true;
        }

        $now = Carbon::now()->toDateTimeString();
        array_key_exists('metadata', $parameters) ? $metadata = $parameters['metadata'] : $metadata = '';
        array_key_exists('count', $parameters) ? $count = $parameters['count'] : $count = 0;
        $insertArray = [
            'status'       => JobStatus::IN_PROGRESS,
            'label'        => $label,
            'user_id'      => $userId,
            'is_admin'     => $isAdmin,
            'progress'     => 0,
            'progress_max' => $count,

            'metadata'   => json_encode($metadata),
            'updated_at' => $now,
            'created_at' => $now,
        ];
        $jobId = $this->db->table(self::JOB_TABLE)->insertGetId(
            $insertArray
        );
        $job->assignJobId($jobId);
        $this->queue->push($job);

        return $jobId;
    }

    /**
     * @param int $id
     * @param int $totalItems
     */
    public function startJob(int $id, int $totalItems)
    {
        $now = Carbon::now()->toDateTimeString();
        $this->db->table(self::JOB_TABLE)->where('id', $id)->update(
            [
                'progress'     => 0,
                'progress_max' => $totalItems,
                'updated_at'   => $now,
            ]
        );
    }

    /**
     * @param int $id
     * @param int $currentItem
     */
    public function updateJobState(int $id, $currentItem)
    {
        $this->db->table(self::JOB_TABLE)->where('id', $id)->update(
            [
                'progress' => $currentItem,
            ]
        );
    }

    /**
     * @param int   $id JobID
     * @param array $metadata
     */
    public function updateMetadata(int $id, array $metadata)
    {
        $this->db->table(self::JOB_TABLE)->where('id', $id)->update(
            [
                'metadata' => json_encode($metadata),
            ]
        );
    }

    /**
     * @param int   $id
     * @param array $metadata
     */
    public function completeJob(int $id, array $metadata = [])
    {
        $totalItems = $this->db->table(self::JOB_TABLE)->where('id', $id)->first(['progress_max']);
        $entry = $this->db->table(self::JOB_TABLE)->where('id', $id);
        if ($metadata) {
            $entry->update(
                [
                    'status'   => JobStatus::COMPLETE,
                    'progress' => $totalItems->progress_max,
                    'metadata' => json_encode($metadata),
                ]
            );
        } else {
            $entry->update(
                [
                    'status'   => JobStatus::COMPLETE,
                    'progress' => $totalItems->progress_max,
                ]
            );
        }
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function checkIfCanceled(int $id): bool
    {
        $status = $this->db->table(self::JOB_TABLE)->where('id', $id)->select('is_canceled')->first()->is_canceled;

        return (bool)$status;
    }

    /**
     * @param int $id
     *
     * @return array
     */
    public function getMetadata(int $id)
    {
        $json = $this->db->table(self::JOB_TABLE)->where('id', $id)->select('metadata')->first()->metadata;

        return json_decode($json, true);
    }

    /**
     * @param int   $id
     * @param array $metadata
     */
    public function failJob(int $id, array $metadata = [])
    {
        $toUpdate = [
            'status' => JobStatus::ERROR,
        ];
        if ($metadata) {
            $toUpdate['metadata'] = json_encode($metadata);
        }
        $this->db->table(self::JOB_TABLE)->where('id', $id)->update(
            $toUpdate
        );
    }

    /**
     * @param int   $id
     * @param array $metadata
     */
    public function cancelJob(int $id, array $metadata = [])
    {
        $toUpdate = [
            'status' => JobStatus::STOPPED,
        ];
        if ($metadata) {
            $toUpdate['metadata'] = json_encode($metadata);
        }
        $this->db->table(self::JOB_TABLE)->where('id', $id)->update(
            $toUpdate
        );
    }

}