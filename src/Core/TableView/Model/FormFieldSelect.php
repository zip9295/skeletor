<?php
namespace Skeletor\Core\TableView\Model;

class FormFieldSelect extends FormField
{
    /**
     * @var array
     */
    public $options;

    /**
     * @param string $name
     * @param string $type
     * @param string $label
     * @param array $options
     * @param string $tooltip
     * @param null|mixed $value
     */
    public function __construct(string $name, string $type, string $label, array $options, $value = null, string $tooltip = '')
    {
        parent::__construct($name, $type, $label, $value, $tooltip,);
        $this->options = $options;
    }
}