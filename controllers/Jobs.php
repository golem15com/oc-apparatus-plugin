<?php namespace Keios\Apparatus\Controllers;

use BackendMenu;
use Backend\Classes\Controller;
use Flash;
use Keios\Apparatus\Contracts\JobStatus;
use Lang;
use Keios\Apparatus\Models\Job;

/**
 * Jobs Back-end Controller
 */
class Jobs extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Keios.Apparatus', 'apparatus', 'jobs');
    }

    /**
     *
     */
    public function index()
    {
        $this->addJs('/plugins/keios/apparatus/assets/js/apparatus-jobs.js');

        $this->asExtension('ListController')->index();
    }

    /**
     * @param $id
     */
    public function view($id)
    {
        /**
         * @var Job|null $job
         */
        $job = Job::where('id', $id)->first();

        if (null === $job) {
            $this->pageTitle = 'Job not found.';
            $this->fatalError = sprintf('Job with id .'.$id.' does not exist.');
        } else {
            $this->addJs('/plugins/keios/apparatus/assets/js/apparatus-jobs.js');
            $this->pageTitle = 'Job '.$id.' - '.$job->label;
            $this->vars['job'] = $job;
        }
    }

    public function isSuperuser(){
        $user = \BackendAuth::getUser();
        return $user->is_superuser;
    }

    /**
     * Deleted checked jobs.
     */
    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {

            foreach ($checkedIds as $jobId) {
                if (!$job = Job::find($jobId)) continue;
                $job->delete();
            }

            Flash::success(Lang::get('keios.apparatus::lang.jobs.delete_selected_success'));
        }
        else {
            Flash::error(Lang::get('keios.apparatus::lang.jobs.delete_selected_empty'));
        }

        return $this->listRefresh();
    }

    /**
     * @return array
     */
    public function onGetProgress(): array
    {
        $ids = post('ids');
        $jobs = Job::whereIn('id', $ids)
            ->select(['id', 'status', 'progress', 'progress_max'])
            ->get()
            ->map(
                function (Job $job) {
                    $stdClass = (object)$job->attributes;
                    $stdClass->statusCode = $job->status;
                    $stdClass->status = $job->getStatus();
                    $stdClass->percent = $job->progressPercent();

                    return $stdClass;
                }
            )
            ->toArray();

        return [
            'jobs' => $jobs,
        ];
    }

    /**
     *
     */
    public function onCancelJob()
    {
        $id = post('id');

        /**
         * @var Job $job
         */
        $job = Job::where('id', $id)->firstOrFail();

        if ($job->canBeCanceled()) {
            $job->is_canceled = true;
            $job->save();

            Flash::success(sprintf('Cancelling job %s - %s...', $job->getKey(), $job->type));
        } else {
            Flash::error(sprintf('Could not cancel job %s - %s.', $job->getKey(), $job->type));
        }
    }

    /**
     * @return Redirect
     */
    public function onForceCancelJob()
    {
        $id = post('id');

        /**
         * @var Job $job
         */
        $job = Job::where('id', $id)->firstOrFail();
        $job->is_canceled = true;
        $job->status = JobStatus::STOPPED;
        $job->save();

        Flash::success(sprintf('Force canceled job %s - %s.', $job->getKey(), $job->type));

        return Redirect::to(Request::url());
    }
}
