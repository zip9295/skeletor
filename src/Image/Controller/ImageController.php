<?php
namespace Skeletor\Image\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Exporter\Contracts\ExporterFactoryInterface;
use Skeletor\Image\Service\Image;
use Tamtamchik\SimpleFlash\Flash;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerInterface as Logger;

class ImageController extends AjaxCrudController
{
    const TITLE_VIEW = "View image";
    const TITLE_CREATE = "Create new image";
    const TITLE_UPDATE = "Edit image: ";
    const TITLE_UPDATE_SUCCESS = "Image updated successfully.";
    const TITLE_CREATE_SUCCESS = "Image created successfully.";
    const TITLE_DELETE_SUCCESS = "Image deleted successfully.";
    const PATH = 'image';

    protected $tableViewConfig = ['writePermissions' => true, 'useModal' => true];

    public function __construct(
        Image $service, Session $session, Config $config, Flash $flash, Engine $template, Logger $logger, ExporterFactoryInterface $exporterFactory
    )
    {
        parent::__construct($service, $session, $config, $flash, $template, $logger, $exporterFactory);
    }

    public function create(): Response
    {
        $errors = [];
        $status = false;
        $message = '';
        $entity = $generalErrors = [];
        $data = $this->getRequest()->getParsedBody();
        if($this->getRequest()->getUploadedFiles() && isset($this->getRequest()->getUploadedFiles()['image'])) {
            $data['image'] = $this->getRequest()->getUploadedFiles()['image'];
        }
        try {
            $entity = $this->service->create($data);
            $data['id'] = $entity->id;
            $status = true;
            $message = $this->translate(static::TITLE_CREATE_SUCCESS);
        } catch (ValidatorException $e) {
            foreach ($this->service->parseErrors() as $key => $error) {
                $errors[] = ['message' => $this->translate($error['message'])];
            }
        } catch (\Exception $e) {
            $generalErrors[] = ['message' => $this->translate($e->getMessage())];
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => $generalErrors,
            'status' => $status,
            'data' => $this->service->getEntityData($entity->id),
            'token' => ($errors !== []) ? $this->csrf()->getHiddenInputString() : null
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function update(): Response
    {
        $errors = [];
        $status = false;
        $message = '';
        $entity = $generalErrors = [];
        $data = $this->getRequest()->getParsedBody();
        try {
            $data['id'] = $this->getRequest()->getAttribute('id');
            $entity = $this->service->update($data);
            $status = true;
            $message = $this->translate(static::TITLE_UPDATE_SUCCESS);
        } catch (ValidatorException $e) {
            foreach ($this->service->parseErrors() as $key => $error) {
                $errors[] = ['message' => $this->translate($error['message'])];
            }
        } catch (\Exception $e) {
            $generalErrors[] = ['message' => $this->translate($e->getMessage())];
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => $generalErrors,
            'status' => $status,
            'data' => $this->service->getEntityData($data['id']),
            'token' => ($errors !== []) ? $this->csrf()->getHiddenInputString() : null,
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function tableHandler()
    {
        $params = $this->getRequest()->getParsedBody();
        $search = ($params['search']) ?? false;
        if($search) {
            $search = filter_var($search, FILTER_SANITIZE_ADD_SLASHES);
        }
        $filter = [];
        $page = 1;
        if (isset($params['filter'])) {
            $filter = $params['filter'];
        }
        if (isset($filter['page'])) {
            $page = $filter['page'];
            unset($filter['page']);
        }
        $order = false;
        $filteredCount = ($filter || $search !== '') ?? true;
        if(isset($params['order'], $params['orderBy'])) {
            $order = ['order' => $params['order'], 'orderBy' => $params['orderBy']];
        }
        if ($this->getSession()->getStorage()->offsetGet('loggedInRole') !== \Skeletor\User\Model\User::ROLE_ADMIN && $this->getSession()->getStorage()->offsetGet('tenantId')) {
            $filter['tenantId'] = $this->getSession()->getStorage()->offsetGet('tenantId');
        }
        if (!isset($params['limit'])) {
            $params['limit'] = 30;
        }
        if (!isset($params['offset'])) {
            $params['offset'] = 0;
        }

        $totalCount = $this->service->getTotalCount();
        $maxPage = ceil($totalCount / $params['limit']);
        if((int)$page > $maxPage) {
            $page = (int)$maxPage;
            $params['offset'] = ($page - 1) * (int)$params['limit'];
        }
        if((int)$page < 1) {
            $page = 1;
            $params['offset'] = 0;
        }

        $data = $this->service->fetchTableData($search, $filter, $params['offset'], $params['limit'], ['order' => 'DESC', 'orderBy' => 'createdAt']);
        if($totalCount !== 0) {
            $bodyData = [
                'data' => $data['entities'],
                'filteredCount' => ($filteredCount) ? $totalCount : null,
                'totalCount' => $totalCount,
                'page' => (int) $page,
                'resultsFound' => count($data['entities']),
                'maxPage' => $maxPage
            ];
            $message = 'ok';
        } else {
            $bodyData = [
                'data' => [],
                'filteredCount' => 0,
                'totalCount' => $totalCount,
                'resultsFound' => 0,
            ];
            $message = $this->translate('No results found');
        }
        $this->getResponse()->getBody()->write(json_encode([
            'entities' => $bodyData,
            'success' => true,
            'message' => $message
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function regenerateImages()
    {
        $this->service->regenerateImages();
        $this->getFlash()->success('Images have been successfully regenerated.');

        return $this->redirect('/post/view/');
    }
}