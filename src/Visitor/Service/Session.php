<?php
namespace Skeletor\Visitor\Service;

use Laminas\Session\ManagerInterface as SessionManager;

class Session
{
    public function __construct(private SessionManager $session) {}

    public function isLoggedIn()
    {
        return ($this->session->getStorage()->offsetGet('loggedIn') > 0);
    }

    public function getLoggedInUserId()
    {
        return $this->session->getStorage()->offsetGet('loggedIn');
    }
}
