<?php

namespace Skeletor\Visitor\Model;

/**
 * Class Client.
 * Represents client user dto.
 *
 * @package Skeletor\User\Model
 */
class Guest extends Visitor
{
    public function __construct()
    {
        parent::__construct('', '', '', '', '', '', '', 'guest',
            '', '');
    }
}
