<?php
namespace Skeletor\User\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Exporter\Contracts\ExporterFactoryInterface;
use Skeletor\User\Service\User as UserService;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class UserController extends AjaxCrudController
{
    const TITLE_VIEW = "View users";
    const TITLE_CREATE = "Create user";
    const TITLE_UPDATE = "Edit user: ";
    const TITLE_UPDATE_SUCCESS = "User updated successfully.";
    const TITLE_CREATE_SUCCESS = "User created successfully.";
    const TITLE_DELETE_SUCCESS = "User deleted successfully.";
    const PATH = 'user';
    const FORM_TITLE_ENTITY_IDENTIFIER = 'email';

    public function __construct(
        UserService $userService, Session $session, Config $config, Flash $flash, Engine $template, Logger $logger
    ) {
        parent::__construct($userService, $session, $config, $flash, $template, $logger);
    }

}