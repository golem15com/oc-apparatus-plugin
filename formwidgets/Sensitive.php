<?php namespace Golem15\Apparatus\FormWidgets;

use Backend\Classes\FormWidgetBase;

class Sensitive extends FormWidgetBase
{
    public $readOnly = false;

    public $disabled = false;

    public $allowCopy = false;

    public $hiddenPlaceholder = '__hidden__';

    public $hideOnTabChange = true;

    protected $defaultAlias = 'g15sensitive';

    public function init()
    {
        $this->alias = preg_replace('/[^\w]+/', '_', $this->alias);

        $this->fillFromConfig([
            'readOnly',
            'disabled',
            'allowCopy',
            'hiddenPlaceholder',
            'hideOnTabChange',
        ]);

        if ($this->formField->disabled || $this->formField->readOnly) {
            $this->previewMode = true;
        }
    }

    public function render()
    {
        $this->prepareVars();

        return $this->makePartial('sensitive');
    }

    public function prepareVars()
    {
        $this->vars['readOnly'] = $this->readOnly;
        $this->vars['disabled'] = $this->disabled;
        $this->vars['hasValue'] = !empty($this->getLoadValue());
        $this->vars['allowCopy'] = $this->allowCopy;
        $this->vars['hiddenPlaceholder'] = $this->hiddenPlaceholder;
        $this->vars['hideOnTabChange'] = $this->hideOnTabChange;
    }

    public function onShowValue()
    {
        return [
            'value' => $this->getLoadValue()
        ];
    }

    public function getSaveValue($value)
    {
        if ($value === $this->hiddenPlaceholder) {
            $value = $this->getLoadValue();
        }

        return $value;
    }

    protected function loadAssets()
    {
        $this->addJs('js/sensitive.js');
        $this->addCss('css/sensitive.css');
    }
}
