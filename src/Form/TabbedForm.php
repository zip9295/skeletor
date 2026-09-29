<?php

namespace Skeletor\Form;

use Skeletor\Form\Base\BaseForm;
use Skeletor\Form\Contracts\TabbedFormInterface;
use Skeletor\Form\Tab\Collection\TabCollection;
use Skeletor\Form\Tab\Contracts\TabCollectionInterface;
use Skeletor\Form\Tab\Contracts\TabInterface;

final class TabbedForm extends BaseForm implements TabbedFormInterface
{
    public function __construct(
        protected string $action,
        protected string $dataAction,
        protected array $csrfToken,
        protected ?string $id = 'crudForm',
        protected ?string $method = 'POST',
        protected ?string $enctype = 'multipart/form-data',
        protected ?string $submitText = 'Save',
        protected bool $readOnly = false,
        protected TabCollectionInterface $tabCollection = new TabCollection()
    )
    {
        parent::__construct($action, $dataAction, $csrfToken, $id, $method, $enctype, $submitText, $readOnly);
    }

    public function addTab(TabInterface $tab): TabInterface
    {
        $this->tabCollection->add($tab);
        return $tab;
    }

    public function getTabCollection(): TabCollectionInterface
    {
        return $this->tabCollection;
    }
}