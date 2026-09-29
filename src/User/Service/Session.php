<?php
namespace Skeletor\User\Service;

use Laminas\Session\ManagerInterface as SessionManager;

class Session
{
    const LOGIN_ERROR_INVALID = 'Invalid credentials provided.';
    const LOGIN_ERROR_NO_EMAIL = 'Email not found in system.';
    const LOGIN_SUCCESS = 'You have successfully logged in.';
    public function __construct(private SessionManager $session) {}

    public function getLoggedInEntityType()
    {
        return $this->session->getStorage()->offsetGet('loggedInEntityType');
    }

    public function getLoggedInRole()
    {
        return $this->session->getStorage()->offsetGet('loggedInRole');
    }

    public function isAdminLoggedIn()
    {
        return ($this->session->getStorage()->offsetGet('loggedInRole') === \Skeletor\User\Model\User::ROLE_ADMIN);
    }

    public function isAdminOrStaff()
    {
        return ($this->session->getStorage()->offsetGet('loggedInRole') === \Skeletor\User\Model\User::ROLE_ADMIN
            || $this->session->getStorage()->offsetGet('loggedInRole') === \Skeletor\User\Model\User::ROLE_STAFF);
    }

    public function getLoggedInTenantId()
    {
        return $this->session->getStorage()->offsetGet('tenantId');
    }

    public function getLoggedInUserId()
    {
        return $this->session->getStorage()->offsetGet('loggedIn');
    }
}
