<?php
namespace Skeletor\Core\TableView\Model;

use Exception;

class Column
{
    /**
     * @var string
     */
    private $name;
    private $viewName;

    private $columnParams = [
        'view' => [
            'priority' => 0,
            'filterable' => false,
            'filterValues' => [],
            'select' => true,
            'itemCount' => false
        ],
        'edit' => [
            'readonly' => false,
            'hidden' => false,
            'priority' => 0,
        ],
        'create' => [
            'hidden' => false,
            'priority' => 0,
        ],
        'jsDataParams' => [
            'orderable' => true,
            'searchable' => true,
            'priority' => 0,
        ]
    ];

    private $supportedParams = ['priority', 'orderable', 'filterable', 'searchable', 'readonly', 'editColumn', 'filterValues', 'select', 'itemCount'];

    /**
     * @param string $name
     * @param int|null $priority
     */
    public function __construct(string $name, string $viewName, ?int $priority = null)
    {
        $this->name = $name;
        $this->viewName = $viewName;
        $this->columnParams['view']['name'] = $viewName;
        $this->columnParams['edit']['name'] = $viewName;
        $this->columnParams['create']['name'] = $viewName;
        if ($priority) {
            $this->columnParams['view']['priority'] = $priority;
        }
    }

    /**
     * Adds param used for list view
     * @param string $paramName
     * @param $paramValue
     * @return Column
     * @throws Exception
     */
    public function addViewParam(string $paramName, $paramValue): Column
    {
        if (in_array($paramName, $this->supportedParams)) {
            $this->columnParams['view'][$paramName] = $paramValue;
        } else {
            throw new Exception("Param: $paramName not supported");
        }
        return $this;
    }

    /**
     *  Adds param used for edit form
     * @param string $paramName
     * @param $paramValue
     * @return Column
     * @throws Exception
     */
    public function addEditParam(string $paramName, $paramValue): Column
    {
        if (in_array($paramName, $this->supportedParams)) {
            $this->columnParams['edit'][$paramName] = $paramValue;
        } else {
            throw new Exception("Param: $paramName not supported");
        }
        return $this;
    }

    /**
     * Adds param used for create form
     * @param string $paramName
     * @param $paramValue
     * @return Column
     * @throws Exception
     */
    public function addCreateParam(string $paramName, $paramValue): Column
    {
        if (in_array($paramName, $this->supportedParams)) {
            $this->columnParams['create'][$paramName] = $paramValue;
        } else {
            throw new Exception("Param: $paramName not supported");
        }
        return $this;
    }

    /**
     * @param string $paramName
     * @param $paramValue
     * @return $this
     * @throws Exception
     */
    public function addJsDataParam(string $paramName, $paramValue): Column
    {
        if (in_array($paramName, $this->supportedParams)) {
            $this->columnParams['jsDataParams'][$paramName] = $paramValue;
        } else {
            throw new Exception("Param: $paramName not supported");
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getColumnDefinitions(): array
    {
        return $this->columnParams;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getViewName(): string
    {
        return $this->viewName;
    }
}