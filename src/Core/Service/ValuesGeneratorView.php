<?php

namespace Skeletor\Core\Service;

use Doctrine\Common\Collections\Collection;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;

class ValuesGeneratorView implements ExtensionInterface
{
    public $template;
    private string $inputBaseName;
    private string $entityName;
    private array|Collection $entities;
    /**
     * @var array
     * If empty array, the entities will have their data extracted as key -> value pairs from the entities
     * If you're working with an object, the array should have 2 indexes. The first one 'getIdMethodName'.
     * The second one 'getValueMethodName'. These two string represent the entity's methods for fetching this data.
     */
    private array $dataExtraction;

    private string|null $searchHandler;

    private string|null $searchEntityFieldValue;

    private string|null $searchEntityFieldViewValue;

    private bool $generateInputsForExisting;

    private string|null $inputNameForExisting;

    public function register(Engine $engine): void
    {
        $engine->registerFunction('getValuesGeneratorView', [$this, 'getValuesGeneratorView']);
    }

    public function getValuesGeneratorView(
        string $inputBaseName,
        string $entityName,
        array|Collection $entities,
        array $dataExtraction = [],
        string|null $searchHandler = null,
        string|null $searchEntityFieldValue = null,
        string|null $searchEntityFieldViewValue = null,
        bool $generateInputsForExisting = false,
        string|null $inputNameForExisting = null): string {
        $this->inputBaseName = $inputBaseName;
        $this->entityName = $entityName;
        $this->entities = $entities;
        $this->dataExtraction = $dataExtraction;
        $this->searchHandler = $searchHandler;
        $this->searchEntityFieldValue = $searchEntityFieldValue;
        $this->searchEntityFieldViewValue = $searchEntityFieldViewValue;
        $this->generateInputsForExisting = $generateInputsForExisting;
        $this->inputNameForExisting = $inputNameForExisting;
        return $this->getView();
    }


    private function getView(): string
    {
        $html = '<div class="valuesGeneratorContainer" data-name="' . $this->inputBaseName . '">';
        $html .= sprintf('<label for="attributeInput">Enter %s value then hit enter</label>', $this->entityName);
        $html .= sprintf('<input type="text" class="form-control valuesGeneratorInput" %s>',
            $this->searchHandler && $this->searchEntityFieldValue ?
                'data-action="' . $this->searchHandler . '" 
                     data-search-field-value="' . $this->searchEntityFieldValue . '"
                     data-search-field-view-value="' . $this->searchEntityFieldViewValue .'"' : '');
        $html .= '<div class="valuesContainer">';
        foreach($this->entities as $key => $entity) {
            $html .= '<div class="valueSingleContainer">';
            if($this->generateInputsForExisting && $this->inputNameForExisting) {
                $html .= '<input type="hidden" name="' . $this->inputNameForExisting .'" value="' . $this->getEntityKey($key, $entity) .'">';
            }
            $html .= sprintf('<span class="valueText">%s</span>',
                $this->getEntityValue($entity));
            $html .= sprintf('<div class="deleteValue" data-id="%s">',
                $this->getEntityKey($key, $entity));
            $html .= ' <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>';
            $html .= '</div>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    private function getEntityKey($key, $entity)
    {
        if (is_object($entity)) {
            if (isset($this->dataExtraction['getIdMethodName'])
                && method_exists($entity, $this->dataExtraction['getIdMethodName'])) {
                return $entity->{$this->dataExtraction['getIdMethodName']}();
            }
            throw new \Error('If an entity is an object 
            a getIdMethodName key must be provided in the dataExtraction array');
        }
        return $key;
    }

    private function getEntityValue($entity)
    {
        if (is_object($entity)) {
            if (isset($this->dataExtraction['getValueMethodName'])
                && method_exists($entity, $this->dataExtraction['getValueMethodName'])) {
                return $entity->{$this->dataExtraction['getValueMethodName']}();
            }
            throw new \Error('If an entity is an object 
            a getValueMethodName key must be provided in the dataExtraction array');
        }
        return $entity;
    }
}