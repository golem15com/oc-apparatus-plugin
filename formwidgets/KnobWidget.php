<?php namespace Golem15\Apparatus\FormWidgets;

use Backend\Classes\FormWidgetBase;

/**
 * Class KnobWidget
 * @package Golem15\KnobWidget\FormWidgets
 */
class KnobWidget extends FormWidgetBase
{
    /**
     * @return array
     */
    public function widgetDetails()
    {
        return [
            'name' => 'golem15.apparatus::lang.labels.knobFormWidget',
            'description' => 'golem15.apparatus::lang.labels.kFwDescription'
        ];
    }

    /**
     * init Method
     */
    public function init()
    {
        $this->addJs('js/jquery.knob.min.js');
        $this->addCss('css/knobWidget.css');
    }

    /**
     * @return mixed
     * @throws \SystemException
     */
    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('knob_form_field');
    }

    /**
     * Prepares the list data
     */
    public function prepareVars()
    {
        $this->vars['name'] = $this->formField->getName();
        $this->vars['default'] = $default = $this->getConfig('default', 0);
        $this->vars['label'] = $this->getConfig('knobLabel');
        $this->vars['comment'] = $this->getConfig('knobComment');

        $this->vars['knobSettings'] = [
            'width' => $this->getConfig('width', 100),
            'height' => $this->getConfig('width', 100),
            'min' => $this->getConfig('min', 0),
            'max' => $this->getConfig('max', 100),
            'readOnly' => $this->getConfig('disabled', 'false'),
            'stopper' => $this->getConfig('stopper', 'true'),
            'step' => $this->getConfig('step', 1),
            'lineCap' => $this->getConfig('linecap', 'default'),
            'angleArc' => $this->getConfig('angleArc', 360),
            'angleOffset' => $this->getConfig('angleOffset', 0),
            'cursor' => $this->getConfig('cursor', 'false'),
            'rotation' => $this->getConfig('rotation', 'clockwise'),
            'thickness' => $this->getConfig('thickness', 0.3),
            'displayInput' => $this->getConfig('displayInput', 'true'),
            'displayPrevious' => $this->getConfig('displayPrevious', 'false'),
            'fgColor' => '#'.$this->getConfig('fgColor', '87CEEB'),
            'bgColor' => '#'.$this->getConfig('bgColor', 'EEEEEE'),
            'inputColor' => '#'.$this->getConfig('inputColor', '87CEEB'),
            'font' => $this->getConfig('font', 'Open Sans'),
            'fontWeight' => $this->getConfig('fontWeight', 'normal'),
        ];

        if ($value = $this->getLoadValue()) {
            $this->vars['value'] = $value;
        } else {
            $this->vars['value'] = $default;
        }
    }

    /**
     * @param \Backend\Classes\The $value
     *
     * @return \Backend\Classes\The
     */
    public function getSaveValue($value)
    {
        return $value;
    }
}
