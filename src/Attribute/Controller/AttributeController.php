<?php

namespace Skeletor\Attribute\Controller;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager;
use League\Plates\Engine;
use Skeletor\Attribute\Repository\AttributeValue;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Attribute\Service\Attribute as Service;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class AttributeController extends AjaxCrudController
{
    const TITLE_VIEW = "View Attribute";
    const TITLE_CREATE = "Create new Attribute";
    const TITLE_UPDATE = "Edit Attribute: ";
    const TITLE_UPDATE_SUCCESS = "Attribute updated successfully.";
    const TITLE_CREATE_SUCCESS = "Attribute created successfully.";
    const TITLE_DELETE_SUCCESS = "Attribute deleted successfully.";
    const PATH = 'Attribute';
    protected $tableViewConfig = ['writePermissions' => true];

    public function __construct(
        Service $service, SessionManager $session, Config $config, Flash $flash, Engine $template, Logger $logger, private AttributeValue $attributeValueRepo
    )
    {
        parent::__construct($service, $session, $config, $flash, $template, $logger);
    }

    public function form(): Response
    {
        $id = (int) $this->getRequest()->getAttribute('id');
        $model = null;
        $this->setGlobalVariable('pageTitle', static::TITLE_CREATE);
        if ($id) {
            $model = $this->service->getById($id);
            $title = $model->getId();
            $attributeValues = $this->attributeValueRepo->fetchAll(['attributeId' => $model->getId()]);
            if (method_exists($model, 'getName')) {
                $title = $model->getName();
            }
            $this->setGlobalVariable('pageTitle', static::TITLE_UPDATE . $title);
        }
        $path = sprintf('/%s/', static::PATH);
        if (strlen($this->tableViewConfig['adminPath'])) {
            $path = sprintf('/%s/%s/', $this->tableViewConfig['adminPath'], static::PATH);
        }

        return $this->respondPartial('form', array_merge($this->formData, [
            'model' => $model,
            'attributeValues' => $attributeValues ?? [],
            'path' => $path
        ]));
    }

}