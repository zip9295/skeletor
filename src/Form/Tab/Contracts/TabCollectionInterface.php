<?php

namespace Skeletor\Form\Tab\Contracts;

interface TabCollectionInterface
{
    public function add(TabInterface $tab): void;
}