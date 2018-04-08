<?php namespace Keios\Apparatus\Models;

use Keios\Apparatus\Classes\QueueJob;
use Model;

/**
 * Job Model
 */
class Job extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'keios_apparatus_jobs';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    protected $jsonable = ['metadata'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    public function getStatus()
    {
        $statusId = $this->status;
        $translations = [
            0 => trans('keios.apparatus::lang.jobs.in_queue'),
            1 => trans('keios.apparatus::lang.jobs.in_progress'),
            2 => trans('keios.apparatus::lang.jobs.complete'),
            3 => trans('keios.apparatus::lang.jobs.error'),
            4 => trans('keios.apparatus::lang.jobs.stopped'),
        ];
        if(array_key_exists($statusId, $translations)){
            return $translations[$statusId];
        }

        return trans('keios.apparatus::lang.jobs.unknown');
    }

    public function progressPercent(): float
    {
        if((int)$this->progress_max === 0){
           $this->progress_max = 1;
        }
        return round(($this->progress * 100) / $this->progress_max);
    }

    public function canBeCanceled(): bool
    {
        return in_array($this->status, [0,1], true);
    }
}
