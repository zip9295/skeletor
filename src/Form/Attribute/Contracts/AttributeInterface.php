<?php

namespace Skeletor\Form\Attribute\Contracts;

interface AttributeInterface
{
    public function __construct(string $key, int|string $value);

    public function getKey(): string;

    public function getValue(): int|string;

}