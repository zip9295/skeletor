<?php

namespace Skeletor\Reference\Controller;

use Skeletor\Reference\Service\Reference;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class ReferenceController extends AjaxCrudController
{
    const TITLE_VIEW = "View reference item";
    const TITLE_CREATE = "Create reference item";
    const TITLE_UPDATE = "Edit reference item: ";
    const TITLE_UPDATE_SUCCESS = "Reference item updated successfully.";
    const TITLE_CREATE_SUCCESS = "Reference item created successfully.";
    const TITLE_DELETE_SUCCESS = "Reference item deleted successfully.";
    const FORM_TITLE_ENTITY_IDENTIFIER = 'title';
    const PATH = 'reference';

    public function __construct(Reference $service, Session $session, Config $config, Flash $flash, Engine $template, Logger $logger)
    {
        parent::__construct($service, $session, $config, $flash, $template, $logger);
    }
}
