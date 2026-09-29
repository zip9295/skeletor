<?php

namespace Skeletor\Lead\Controller;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Core\Mapper\NotFoundException;
use Skeletor\Exporter\Contracts\ExporterFactoryInterface;
use Skeletor\Lead\Service\Lead;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class LeadController extends AjaxCrudController
{
    const TITLE_VIEW = "View lead";
    const TITLE_CREATE = "Create new lead";
    const TITLE_UPDATE = "Edit lead: ";
    const TITLE_UPDATE_SUCCESS = "lead updated successfully.";
    const TITLE_CREATE_SUCCESS = "lead created successfully.";
    const TITLE_DELETE_SUCCESS = "lead deleted successfully.";
    const PATH = 'lead';
    const FORM_TITLE_ENTITY_IDENTIFIER = 'email';

    public function __construct(
        Lead $service, Session $session, Config $config, Flash $flash, Engine $template, Logger $logger, ?ExporterFactoryInterface $exporterFactory = null
    ) {
        parent::__construct($service, $session, $config, $flash, $template, $logger, $exporterFactory);
    }

}