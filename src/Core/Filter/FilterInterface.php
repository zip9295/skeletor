<?php
namespace Skeletor\Core\Filter;

interface FilterInterface
{
    public function getErrors();

    public function filter(array $data) : array;
}