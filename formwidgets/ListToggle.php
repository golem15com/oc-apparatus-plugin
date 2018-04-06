<?php

namespace Keios\Apparatus\FormWidgets;

use Backend\Classes\ListColumn;
use Lang;
use Model;

/**
 * Class ListToggle based on Inetis ListSwitch MIT OctoberCMS Plugin
 *
 * @package Keios\Apparatus\FormWidgets
 */
class ListToggle
{
    /**
     * Default field configuration
     * all these params can be overrided by column config
     * @var array
     */
    private static $defaultFieldConfig = [
        'icon'       => true,
        'titleTrue'  => 'keios.apparatus::lang.listtoggle.title_true',
        'titleFalse' => 'keios.apparatus::lang.listtoggle.title_false',
        'textTrue'   => 'keios.apparatus::lang.listtoggle.text_true',
        'textFalse'  => 'keios.apparatus::lang.listtoggle.text_false',
        'request'    => 'onSwitchInetisListField',
    ];

    /**
     * @var array
     */
    private static $listConfig = [];

    /**
     * @param string $field
     * @param array  $config
     */
    public static function storeFieldConfig(string $field, array $config)
    {
        self::$listConfig[$field] = array_merge(self::$defaultFieldConfig, $config, ['name' => $field]);
    }

    /**
     * @param            $value
     * @param ListColumn $column
     * @param Model      $record
     * @return string
     */
    public static function render($value, ListColumn $column, Model $record)
    {
        $field = new self($value, $column, $record);
        $config = $field->getConfig();

        return '
<a href="javascript:;"
    data-request="'.$config['request'].'"
    data-request-data="'.$field->getRequestData().'"
    data-stripe-load-indicator
    title="'.$field->getButtonTitle().'">
    '.$field->getButtonValue().'
</a>
';
    }

    /**
     * ListToggle constructor.
     * @param            $value
     * @param ListColumn $column
     * @param Model      $record
     */
    public function __construct($value, ListColumn $column, Model $record)
    {
        $this->name = $column->columnName;
        $this->value = $value;
        $this->column = $column;
        $this->record = $record;
    }

    /**
     * @param null $config
     * @return mixed
     */
    private function getConfig($config = null)
    {
        if (null === $config) {
            return self::$listConfig[$this->name];
        }

        return self::$listConfig[$this->name][$config];
    }

    /**
     * @return string
     */
    public function getRequestData(): string
    {
        $modelClass = str_replace('\\', '\\\\', get_class($this->record));
        $data = [
            "id: {$this->record->{$this->record->getKeyName()}}",
            "field: '$this->name'",
            "model: '$modelClass'",
        ];
        if (post('page')) {
            $data[] = "page: ".post('page');
        }

        return implode(', ', $data);
    }

    /**
     * @return string
     */
    public function getButtonValue(): string
    {
        if (!$this->getConfig('icon')) {
            return Lang::get($this->getConfig($this->value ? 'textTrue' : 'textFalse'));
        }
        if ($this->value) {
            return '<i class="oc-icon-check"></i>';
        }

        return '<i class="oc-icon-times"></i>';
    }

    /**
     * @return string
     */
    public function getButtonTitle(): string
    {
        return Lang::get($this->getConfig($this->value ? 'titleTrue' : 'titleFalse'));
    }
}