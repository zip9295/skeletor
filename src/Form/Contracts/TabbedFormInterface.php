<?php

namespace Skeletor\Form\Contracts;

use Skeletor\Form\Tab\Contracts\TabCollectionInterface;
use Skeletor\Form\Tab\Contracts\TabInterface;

interface TabbedFormInterface extends FormDataInterface
{
    public function addTab(TabInterface $tab): TabInterface;

    public function getTabCollection(): TabCollectionInterface;
}