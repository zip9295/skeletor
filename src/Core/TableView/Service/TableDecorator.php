<?php
namespace Skeletor\Core\TableView\Service;

use League\Plates\Engine;
use Skeletor\Translator\Service\Translator;

class TableDecorator
{
    const DEFAULTS = [
        'name' => null, 'label' => null, 'sortable' => true, 'editColumn' => false, 'filterClass' => false,
        'filterData' => false,
        //@TODO test
        'select' => true, 'itemCount' => [0]
    ];

    protected $definitions;

    public function __construct(private Engine $template, array $columnDefinitions, protected array $filtersToHide = [])
    {
        foreach ($columnDefinitions as $definition) {
            $this->definitions[$definition['name']] = array_merge(self::DEFAULTS, $definition);
        }
    }

    public function generateFiltersHtml()
    {
        $html = '<div id="tableFilters">';
        foreach ($this->definitions as $columnDef) {
            if ($columnDef['filterData']) {
                $columnName = $columnDef['name'];
                $filters = $columnDef['filterData'];
                if (count($filters) > 0) {
                    $i = 0;
                    if(!$columnDef['select']) {
                        $html .= '<div class="filterLinks">';
                        foreach($filters as $id => $value) {
                            $active = (isset($_GET[$columnName]) && $_GET[$columnName] == $id) ? ' primary' : '';
                            $html .= sprintf('<a class="small%s" href="?%s=%d">%s<span class="count"> (%s) </span></a>',
                                $active, $columnName, $id, $value, $columnDef['itemCount'][$i]);
                            $i++;
                        }
                        $html .= '</div>';
                        continue;
                    }
                    $isHidden = isset($columnDef['hiddenFilter']) ? ' hidden' : '';
                    if($isHidden === '' && in_array($columnDef['name'], $this->filtersToHide)) {
                        $isHidden = ' hidden';
                    }
                    $html .= '<div class="filterContainer' . $isHidden .'" data-name="' . $columnName .'">';
                    $html .= '<div class="selectSearchContainer">';
                    $html .= sprintf('<label>%s</label>', $this->template->make('test')->t('Filter by') .': '. $columnDef['label']);
                    $html .= '<select class="tableFilter input" name="' . $columnName . '">';
                    $html .= '<option value="-1">---</option>';
                    foreach ($filters as $id => $value) {
                        if ($value === null) {
                            $id = '-1';
                            $value = 'N/A';
                        }
                        $html .= '<option value="' . $id . '">' . $value . '</option>';
                    }
                    $html .= '</select>';
                    $html .= '<div class="selectSearchOverlay"></div>';
                    $html .= '</div>';
                    $html .= '</div>';
                }
            }
        }
        $html .= '</div>';

        return $html;
    }

    public function generateColumnHeadersForView(): string
    {
        $sortedColumns = $this->sortColumnsByPriority($this->definitions);
        $html = '';
        $key = 2;
        foreach ($sortedColumns as $definitions) {
            $width = isset($definitions['width']) ? ' style="width:' . $definitions['width'] . '" ' : ' ';
            $html .= '<th' . $width . 'class="dataColumn" data-index="'.$key.'"';
            $html .= sprintf('data-%s="%s"', 'column-name', $definitions['name']);
            $html .= '>' . $this->template->make('view')->t($definitions['label'])
                . ($definitions['sortable'] ? $this->getSortableHtml() : '')
                . (
                isset($definitions['rangeFilter'],$definitions['rangeFilter']['type']) ?
                    $this->getRangeHTML($definitions['rangeFilter']['type'], $definitions['name']) : ''
                )
                . '</th>';
            $key++;
        }

        return $html;
    }

    /**
     * @param $columnDefArray
     * @return array
     */
    private function sortColumnsByPriority($columnDefArray): array
    {
        $order = $sortedColumns = [];
        $default = 0;
        foreach ($columnDefArray as $columnDef) {
            $priority = isset($columnDef['priority']) ? $columnDef['priority'] : $default;
            $order[$priority] = $columnDef;
            $default++;
        }
        $orderKeys = array_keys($order);
        sort($orderKeys);
        foreach ($orderKeys as $key) {
            $sortedColumns[] = $order[$key];
        }

        return $sortedColumns;
    }

    /**
     * @return string
     */
    private function getSortableHtml(): string
    {
        return '<div class="sort">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M151.6 469.6C145.5 476.2 137 480 128 480s-17.5-3.8-23.6-10.4l-88-96c-11.9-13-11.1-33.3 2-45.2s33.3-11.1 45.2 2L96 365.7V64c0-17.7 14.3-32 32-32s32 14.3 32 32V365.7l32.4-35.4c11.9-13 32.2-13.9 45.2-2s13.9 32.2 2 45.2l-88 96zM320 480c-17.7 0-32-14.3-32-32s14.3-32 32-32h32c17.7 0 32 14.3 32 32s-14.3 32-32 32H320zm0-128c-17.7 0-32-14.3-32-32s14.3-32 32-32h96c17.7 0 32 14.3 32 32s-14.3 32-32 32H320zm0-128c-17.7 0-32-14.3-32-32s14.3-32 32-32H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H320zm0-128c-17.7 0-32-14.3-32-32s14.3-32 32-32H544c17.7 0 32 14.3 32 32s-14.3 32-32 32H320z"/></svg>
            </div>';
    }

    /**
     * @param $type
     * @param $columnName
     * @return string
     */
    private function getRangeHTML($type, $columnName): string
    {
        return '<div data-column-name="' . $columnName .'" class="rangeFilter" data-range-filter-type="'.$type.'"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                                    <path d="M0 416c0-17.7 14.3-32 32-32l54.7 0c12.3-28.3 40.5-48 73.3-48s61 19.7 73.3 48L480 384c17.7 0 32 14.3 32 32s-14.3 32-32 32l-246.7 0c-12.3 28.3-40.5 48-73.3 48s-61-19.7-73.3-48L32 448c-17.7 0-32-14.3-32-32zm192 0a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zM384 256a32 32 0 1 0 -64 0 32 32 0 1 0 64 0zm-32-80c32.8 0 61 19.7 73.3 48l54.7 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-54.7 0c-12.3 28.3-40.5 48-73.3 48s-61-19.7-73.3-48L32 288c-17.7 0-32-14.3-32-32s14.3-32 32-32l246.7 0c12.3-28.3 40.5-48 73.3-48zM192 64a32 32 0 1 0 0 64 32 32 0 1 0 0-64zm73.3 0L480 64c17.7 0 32 14.3 32 32s-14.3 32-32 32l-214.7 0c-12.3 28.3-40.5 48-73.3 48s-61-19.7-73.3-48L32 128C14.3 128 0 113.7 0 96S14.3 64 32 64l86.7 0C131 35.7 159.2 16 192 16s61 19.7 73.3 48z"/>
                                    </svg>
                                    <div class="rangeFilterContainer">
                                    <input type="' . $type .'" class="input from" placeholder="From">
                                    <input type="' . $type .'" class="input to" placeholder="To">
                                    <div class="rangeActions">
                                        <div class="rangeApply btn primary">Apply</div>
                                        <div class="rangeClear btn">Clear</div>
                                    </div>
                                    <div class="rangeClose">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                                            <path d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"/>
                                        </svg>
                                    </div>
                                </div>
                                  </div>';
    }
}