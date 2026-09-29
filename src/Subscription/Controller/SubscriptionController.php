<?php

namespace Skeletor\Subscription\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Subscription\Service\Subscription as Service;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class SubscriptionController extends AjaxCrudController
{
    const TITLE_VIEW = "View subscription";
    const TITLE_CREATE = "Create new subscription";
    const TITLE_UPDATE = "Edit subscription: ";
    const TITLE_UPDATE_SUCCESS = "Subscription updated successfully.";
    const TITLE_CREATE_SUCCESS = "Subscription created successfully.";
    const TITLE_DELETE_SUCCESS = "Subscription deleted successfully.";
    const PATH = 'subscription';
    protected $tableViewConfig = ['writePermissions' => true];

    public function __construct(
        Service $service, SessionManager $session, Config $config, Flash $flash, Engine $template, Logger $logger
    ) {
        parent::__construct($service, $session, $config, $flash, $template, $logger);
    }
}