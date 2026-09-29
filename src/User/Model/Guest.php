<?php

namespace Skeletor\User\Model;

/**
 * Class Client.
 * Represents client user dto.
 *
 * @package Skeletor\User\Model
 */
class Guest extends User
{
    public function __construct()
    {
        parent::__construct('', '', '', '', '', self::ROLE_GUEST, '', 'guest', '', '');
    }
}
