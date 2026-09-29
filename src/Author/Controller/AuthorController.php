<?php

namespace Skeletor\Author\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Author\Service\Author as Service;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class AuthorController extends AjaxCrudController
{
    const TITLE_VIEW = "View author";
    const TITLE_CREATE = "Create new author";
    const TITLE_UPDATE = "Edit author: ";
    const TITLE_UPDATE_SUCCESS = "Author updated successfully.";
    const TITLE_CREATE_SUCCESS = "Author created successfully.";
    const TITLE_DELETE_SUCCESS = "Author deleted successfully.";
    const PATH = 'author';
    protected $tableViewConfig = ['writePermissions' => true];

    public function __construct(
        Service $service, SessionManager $session, Config $config, Flash $flash, Engine $template, Logger $logger
    )
    {
        parent::__construct($service, $session, $config, $flash, $template, $logger);
    }
}