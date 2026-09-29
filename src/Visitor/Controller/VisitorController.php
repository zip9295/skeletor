<?php
namespace Skeletor\Visitor\Controller;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Visitor\Filter\Visitor;
use Skeletor\Visitor\Repository\VisitorRepository as VisitorRepo;
use Skeletor\Visitor\Service\Visitor as VisitorService;
use Tamtamchik\SimpleFlash\Flash;
use Skeletor\Subscription\Service\Subscription;
use Psr\Log\LoggerInterface as Logger;

class VisitorController extends AjaxCrudController
{
    const TITLE_VIEW = "View visitors";
    const TITLE_CREATE = "Create visitor";
    const TITLE_UPDATE = "Edit visitor: ";
    const TITLE_UPDATE_SUCCESS = "Visitor updated successfully.";
    const TITLE_CREATE_SUCCESS = "Visitor created successfully.";
    const TITLE_DELETE_SUCCESS = "Visitor deleted successfully.";
    const PATH = 'visitor';

    /**
     * @var User
     */
    protected $userFilter;

    protected $tableViewConfig = ['writePermissions' => true, 'useModal' => true];

    /**
     * @param VisitorService $userService
     * @param Session $session
     * @param Config $config
     * @param Flash $flash
     * @param Engine $template
     * @param Logger $logger
     */
    public function __construct(
        VisitorService $service, Session $session, Config $config, Flash $flash, Engine $template, Logger $logger,
//        private Subscription $subscription
    ) {
        parent::__construct($service, $session, $config, $flash, $template, $logger);
    }

    public function form(): Response
    {
//        $this->formData['subscriptions'] = $this->subscription->getFilterData();

        return parent::form();
    }

}