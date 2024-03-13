<?php

namespace Golem15\Apparatus\FormWidgets;

use Backend\Classes\ListColumn;
use Lang;
use Model;

/**
 * Class ListToggle based on Inetis ListSwitch MIT OctoberCMS Plugin
 *
 * @package Golem15\Apparatus\FormWidgets
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
        'titleTrue'  => 'golem15.apparatus::lang.listtoggle.title_true',
        'titleFalse' => 'golem15.apparatus::lang.listtoggle.title_false',
        'textTrue'   => 'golem15.apparatus::lang.listtoggle.text_true',
        'textFalse'  => 'golem15.apparatus::lang.listtoggle.text_false',
        'request'    => 'onSwitchInetisListField',
        'readOnly'   => false,
    ];

    /**
     * @var array
     */
    private static $listConfig = [];

    /**
     * @param string $field
     * @param array  $config
     */
    public static function storeFieldConfig(string $field, array $config): void
    {
        self::$listConfig[$field] = array_merge(self::$defaultFieldConfig, $config, ['name' => $field]);
    }

    /**
     * @param string     $value
     * @param ListColumn $column
     * @param Model      $record
     * @return string
     */
    public static function render(string $value, ListColumn $column, Model $record): string
    {
        $field = new self($value, $column, $record);
        $config = $field->getConfig();
        if ($config['readOnly']) {
            return $field->getButtonValue();
        }

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
    public function __construct(string $value, ListColumn $column, Model $record)
    {
        $this->name = $column->columnName;
        $this->value = $value;
        $this->column = $column;
        $this->record = $record;
    }

    /**
     * @param null|string $config
     * @return mixed
     */
    private function getConfig(?string $config = null)
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
        $modelClass = str_replace('\\', '\\\\', \get_class($this->record));
        $data = [
            "id: {$this->record->{$this->record->getKeyName()}}",
            "field: '$this->name'",
            "model: '$modelClass'",
        ];
        if (post('page')) {
            $data[] = 'page: '.post('page');
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
