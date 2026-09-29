<?php
namespace Skeletor\Core\TableView\Model;

class FormField
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $type;
    /**
     * @var string
     */
    public $label;
    /**
     * @var string
     */
    public $tooltip;
    /**
     * @var null|string
     */
    public $value;

    /**
     * @param string $name
     * @param string $type
     * @param string $label
     * @param string $tooltip
     * @param null|mixed $value
     */
    public function __construct(string $name, string $type, string $label, $value = null, string $tooltip = '')
    {
        $this->name = $name;
        $this->type = $type;
        $this->label = $label;
        $this->tooltip = $tooltip;
        $this->value = $value;
    }
}