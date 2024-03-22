$(function () {

    function getAllVisibleJobsElems() {
        return $('[data-job-progress]');
    }

    function filterOnlyChangeableJobsElems(jobs) {
        return jobs.filter(function () {
            return $(this).data('job-status') < 2;
        });
    }

    function mapJobsElemsToJobIds(jobs) {
        return jobs.map(function () {
            return $(this).data('job-id');
        });
    }

    function setCurrentProgress(data) {
        data.jobs.forEach(function (job) {
            var $status = $('[data-job-id="' + job.id + '"]');
            var $bar = $('[data-job-progress="' + job.id + '"]');
            var $text = $('[data-job-progress-text="' + job.id + '"]');

            //$status.html(job.status);

            if (job.percent == 100 || job.statusCode > 1) {
                $bar.parent().remove();
                $text.parent().remove();
                $('#job-cancel-btn').remove();
                $('#job-force-cancel-btn').remove();
                return;
            }

            $bar.attr('aria-valuenow', job.progress_current);
            $bar.attr('aria-valuemax', job.progress_max);
            $bar.css('width', job.percent + '%');
            $text.html(job.percent);
        });
    }

    function refreshProgress() {
        var allJobElements = getAllVisibleJobsElems();
        var jobsRequiringUpdate = mapJobsElemsToJobIds(filterOnlyChangeableJobsElems(allJobElements)).toArray();
        if (jobsRequiringUpdate.length > 0) {
            $.request('onGetProgress', {data: {ids: jobsRequiringUpdate}, success: setCurrentProgress});
        }

        allJobElements.toArray().forEach(function (jobElem) {
            if ($(jobElem).data('job-status') > 1) {
                var id = $(jobElem).data('job-id');
                var $bar = $('[data-job-progress="' + id + '"]');
                var $text = $('[data-job-progress-text="' + id + '"]');
   //             $bar.parent().remove();
   //             $text.parent().remove();
            }
        });

        $('#progress-container').removeClass('hidden');

        nextRefreshCycle();
    }

    function nextRefreshCycle() {
        setTimeout(refreshProgress, 2000);
    }

    refreshProgress();
});
