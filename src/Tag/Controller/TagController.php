<?php
namespace Skeletor\Tag\Controller;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Tag\Service\Tag;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class TagController extends AjaxCrudController
{
    const TITLE_VIEW = "Tags";
    const TITLE_CREATE = "Create new tag";
    const TITLE_UPDATE = "Edit tag: ";
    const TITLE_UPDATE_SUCCESS = "Tag updated successfully.";
    const TITLE_CREATE_SUCCESS = "Tag created successfully.";
    const TITLE_DELETE_SUCCESS = "Tag deleted successfully.";
    const PATH = 'tag';

    protected $tableViewConfig = ['writePermissions' => true, 'useModal' => true];

    public function __construct(Tag $service, SessionManager $session, Config $config, Flash $flash, Engine $template, Logger $logger)
    {
        parent::__construct($service, $session, $config, $flash, $template, $logger);
    }
    public function form(): Response
    {
        $id = $this->getRequest()->getAttribute('id');
        $tag = null;
        $imagePath = null;
        $data = [];
        if ($id) {
            $tag = $this->service->getById($id);
            $this->setGlobalVariable('pageTitle', self::TITLE_UPDATE . $tag->getTitle());
        }
        return $this->respondPartial('form', [
            'model' => $tag,
            'data' => $data,
            'imagePath' => $imagePath
        ]);
    }
}