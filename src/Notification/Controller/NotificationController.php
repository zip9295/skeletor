<?php

namespace Skeletor\Notification\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Notification\Service\Notification;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class NotificationController extends AjaxCrudController
{
    const TITLE_VIEW = "View Notifications";
    const TITLE_CREATE = "Create new notification";
    const TITLE_UPDATE = "Edit notification: ";
    const TITLE_UPDATE_SUCCESS = "Notification updated successfully.";
    const TITLE_CREATE_SUCCESS = "Notification created successfully.";
    const TITLE_DELETE_SUCCESS = "Notification deleted successfully.";
    const PATH = 'notification';

    public function __construct(Notification $service, Session $session, Config $config, Flash $flash, Engine $template, Logger $logger)
    {
        parent::__construct($service, $session, $config, $flash, $template, $logger);
    }
}