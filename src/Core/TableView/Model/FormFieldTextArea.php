<?php
namespace Skeletor\Core\TableView\Model;

class FormFieldTextArea extends FormField
{
    /**
     * @var int
     */
    public $rows;
    /**
     * @var int
     */
    public $cols;

    /**
     * @param string $name
     * @param string $type
     * @param string $label
     * @param int $rows
     * @param int $cols
     * @param string $tooltip
     * @param null|mixed $value
     */
    public function __construct(string $name, string $type, string $label, int $rows = 10, int $cols = 20, $value = null, string $tooltip = '')
    {
        parent::__construct($name, $type, $label, $value, $tooltip);

        $this->rows = $rows;
        $this->cols = $cols;
    }
}