<?php namespace Keios\Apparatus\Behaviors;

use Str;
use Lang;
use View;
use Response;
use Backend;
use BackendAuth;
use Backend\Classes\ControllerBehavior;
use Backend\Behaviors\ImportExportController\TranscodeFilter;
use Illuminate\Database\Eloquent\MassAssignmentException;
use League\Csv\Reader as CsvReader;
use League\Csv\Writer as CsvWriter;
use ApplicationException;
use SplTempFileObject;
use Exception;

/**
 * Adds features for importing and exporting data.
 *
 * This behavior is implemented in the controller like so:
 *
 *     public $implement = [
 *         'Backend.Behaviors.ImportExportController',
 *     ];
 *
 *     public $importExportConfig = 'config_import_export.yaml';
 *
 * The `$importExportConfig` property makes reference to the configuration
 * values as either a YAML file, located in the controller view directory,
 * or directly as a PHP array.
 *
 * @package october\backend
 * @author Alexey Bobkov, Samuel Georges
 */
class BackgroundImportExportController extends Backend\Behaviors\ImportExportController
{

    //
    // Importing AJAX
    //

    public function onImport()
    {
        try {
            $model = $this->importGetModel();
            $matches = post('column_match', []);

            if ($optionData = post('ImportOptions')) {
                $model->fill($optionData);
            }

            $importOptions = $this->getFormatOptionsFromPost();
            $importOptions['sessionKey'] = $this->importUploadFormWidget->getSessionKey();
            $importOptions['firstRowTitles'] = post('first_row_titles', false);

            // here is difference - in normal behavior we don't get anything from here. in background one, we get jobId
            $jobId = $model->import($matches, $importOptions);

            $this->vars['jobId'] = $jobId;
            $this->vars['returnUrl'] = \Backend::url('keios/apparatus/jobs/view/'.$jobId);
        }
        catch (MassAssignmentException $ex) {
            $this->controller->handleError(new ApplicationException(Lang::get(
                'backend::lang.model.mass_assignment_failed',
                ['attribute' => $ex->getMessage()]
            )));
        }
        catch (Exception $ex) {
            $this->controller->handleError($ex);
        }

        return $this->importExportMakePartial('import_result_form');
    }

    public function onImportLoadForm()
    {
        try {
            $this->checkRequiredImportColumns();
        }
        catch (Exception $ex) {
            $this->controller->handleError($ex);
        }

        return $this->importExportMakePartial('import_form');
    }

    //
    // Importing
    //

    /**
     * Prepares the view data.
     * @return void
     */
    public function prepareImportVars()
    {
        $this->vars['importUploadFormWidget'] = $this->importUploadFormWidget;
        $this->vars['importOptionsFormWidget'] = $this->importOptionsFormWidget;
        $this->vars['importDbColumns'] = $this->getImportDbColumns();
        $this->vars['importFileColumns'] = $this->getImportFileColumns();

        // Make these variables available to widgets
        $this->controller->vars += $this->vars;
    }

    /**
     * Returns the file format options from postback. This method
     * can be used to define presets.
     * @return array
     */
    protected function getFormatOptionsFromPost()
    {
        $presetMode = post('format_preset');

        $options = [
            'delimiter' => null,
            'enclosure' => null,
            'escape' => null,
            'encoding' => null
        ];

        if ($presetMode == 'custom') {
            $options['delimiter'] = post('format_delimiter');
            $options['enclosure'] = post('format_enclosure');
            $options['escape'] = post('format_escape');
            $options['encoding'] = post('format_encoding');
        }

        return $options;
    }

}
