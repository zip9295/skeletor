<?php


namespace Skeletor\Category\Model;


class MarginRule
{
   public function __construct(private int $price, private string $margin) {}

    /**
     * @return string
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @return string
     */
    public function getMargin(): string
    {
        return $this->margin;
    }

}