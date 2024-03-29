<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 1/3/18
 * Time: 9:55 AM
 */

namespace Golem15\Apparatus\Classes;

use Golem15\Apparatus\Contracts\ApparatusQueueJob;
use Golem15\Apparatus\Contracts\JobStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Database\Connection;

/**
 * Class JobManager
 * @package Golem15\Apparatus\Classes
 */
class JobManager
{
    const JOB_TABLE = 'golem15_apparatus_jobs';

    /**
     * @var Connection
     */
    private $db;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * This flag will remove successfully completed  DB to not clutter controller
     * @var bool
     */
    private $simpleJob = false;

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
     */
    public function dispatch(ApparatusQueueJob $job, string $label, array $parameters = [], int $delay = 0): int
    {
        $isAdmin = false;
        $userId = null;
        if (class_exists(\Auth::class) && $user = \Auth::getUser()) {
            $userId = $user->id;
            $isAdmin = false;
        }
        if ($user = \BackendAuth::getUser()) {
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
            'metadata'     => json_encode($metadata),
            'updated_at'   => $now,
            'created_at'   => $now,
        ];
        $jobId = $this->db->table(self::JOB_TABLE)->insertGetId(
            $insertArray
        );
        $job->assignJobId($jobId);
        if ($delay > 0) {
            $this->queue->later($delay, $job);
        } else {
            $this->queue->push($job);
        }

        return $jobId;
    }

    /**
     * @param int $id
     * @param int $totalItems
     */
    public function startJob(int $id, int $totalItems): void
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
    public function updateJobState(int $id, int $currentItem, array $metadata = []): void
    {
        $this->db->table(self::JOB_TABLE)->where('id', $id)->update(
            [
                'progress' => $currentItem,
            ]
        );
        if ($metadata) {
            $this->updateMetadata($id, $metadata);
        }
    }

    /**
     * @param int   $id JobID
     * @param array $metadata
     */
    public function updateMetadata(int $id, array $metadata): void
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
    public function completeJob(int $id, array $metadata = []): void
    {
        $maxProgress = 1;
        $totalItems = $this->db->table(self::JOB_TABLE)->where('id', $id)->first(['progress_max']);
        if ($totalItems) {
            $maxProgress = $totalItems->progress_max;
        }
        $entry = $this->db->table(self::JOB_TABLE)->where('id', $id);
        if ($this->isSimpleJob()) {
            $entry->delete();
        } elseif ($metadata) {
            $entry->update(
                [
                    'status'   => JobStatus::COMPLETE,
                    'progress' => $maxProgress,
                    'metadata' => json_encode($metadata),
                ]
            );
        } else {
            $entry->update(
                [
                    'status'   => JobStatus::COMPLETE,
                    'progress' => $maxProgress,
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
    public function getMetadata(int $id): array
    {
        $json = $this->db->table(self::JOB_TABLE)->where('id', $id)->select('metadata')->first()->metadata;
        $decoded = json_decode($json, true);

        return $decoded ?: [];
    }

    /**
     * @param int   $id
     * @param array $metadata
     */
    public function failJob(int $id, array $metadata = []): void
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
    public function cancelJob(int $id, array $metadata = []): void
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

    /**
     * @return bool
     */
    public function isSimpleJob(): bool
    {
        return $this->simpleJob;
    }

    /**
     * @param bool $simpleJob
     */
    public function setSimpleJob(bool $simpleJob): void
    {
        $this->simpleJob = $simpleJob;
    }

}
