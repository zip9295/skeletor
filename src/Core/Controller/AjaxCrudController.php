<?php
namespace Skeletor\Core\Controller;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use Laminas\Session\ManagerInterface;
use League\Plates\Engine;
use Psr\Log\LoggerInterface;
use Skeletor\Core\Service\CrudService as Service;
use Skeletor\Core\TableView\Service\TableDecorator;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorException;
use Skeletor\Exporter\Contracts\ExporterFactoryInterface;
use Tamtamchik\SimpleFlash\Flash;

class AjaxCrudController extends Controller
{
    const TITLE_VIEW = "View entity";
    const TITLE_CREATE = "Create new entity";
    const TITLE_UPDATE = "Edit entity: ";
    const TITLE_UPDATE_SUCCESS = "Entity updated successfully.";
    const TITLE_CREATE_SUCCESS = "Entity created successfully.";
    const TITLE_DELETE_SUCCESS = "Entity deleted successfully.";
    const TITLE_DELETE_ERROR = "Could not delete entity";
    const PATH = 'entity';
    const FORM_TITLE_ENTITY_IDENTIFIER = null;

    protected $formData = [];

    /**
     * Defaults for every table view.
     *
     * These live in a constant rather than in $tableViewConfig because a subclass that redeclares
     * that property *replaces* the array outright — PHP does not merge inherited property
     * defaults. Every controller declaring its own $tableViewConfig was therefore silently
     * dropping 'createButton', which then warned on render. initTableViewSettins() merges these
     * back in, so a subclass only declares the keys it actually wants to change.
     */
    private const TABLE_VIEW_DEFAULTS = [
        'createButton' => true,
    ];

    protected $tableViewConfig = [];

    protected array $uncountableFilters = [];

    public function __construct(
        protected Service $service, ManagerInterface $session, Config $config, Flash $flash, Engine $template,
        LoggerInterface $logger,
        protected ?ExporterFactoryInterface $exporterFactory = null
    ) {
        parent::__construct($template, $config, $session, $flash, $logger);
        $this->initTableViewSettins();
        $userId = $session->getStorage()->offsetGet('loggedIn');
        if (!$userId) {
            return $this->redirect('/');
        }
    }

    private function initTableViewSettins()
    {
        // Subclass values win; anything it did not declare falls back to the defaults.
        $this->tableViewConfig = array_merge(self::TABLE_VIEW_DEFAULTS, $this->tableViewConfig);

        $this->tableViewConfig['createAction'] = sprintf('/%s/form/', static::PATH);
        if (!isset($this->tableViewConfig['entityPath']) || strlen($this->tableViewConfig['entityPath']) === 0) {
            $this->tableViewConfig['entityPath'] = static::PATH;
        }
        $this->tableViewConfig['adminPath'] = $this->getConfig()->offsetGet('adminPath');
        if (strlen($this->getConfig()->offsetGet('adminPath'))) {
            $this->tableViewConfig['createAction'] = sprintf('/%s/%s/form/', $this->getConfig()->offsetGet('adminPath'), static::PATH);
        }
        $this->tableViewConfig['primaryKeyIdentifier'] = $this->service->getPrimaryKeyIdentifier();
    }

    public function create(): Response
    {
        $errors = [];
        $status = false;
        $message = '';
        $entity = $generalErrors = [];
        $data = $this->getRequest()->getParsedBody();
        $token = null;
        try {
            $entity = $this->service->create($data);
            $status = true;
            $message = $this->translate(static::TITLE_CREATE_SUCCESS);
        } catch (InvalidFormTokenException $e) {
            $errors[] = ['message' => $this->translate('Access denied. Please refresh the page and try again.')];
        } catch (ValidatorException $e) {
            foreach ($this->service->parseErrors() as $key => $error) {
                $errors[] = ['message' => $this->translate($error['message'])];
            }
            if(count($this->service->parseErrors()) > 0) {
                $token = $this->csrf()->getHiddenInputString();
            }
        } catch (\Exception $e) {
//            $this->logger->error('Create failed: ' . $e->getMessage(), ['exception' => $e]);
            $generalErrors[] = ['message' => $this->translate('An unexpected error occurred. Please try again.')];
            $token = $this->csrf()->getHiddenInputString();
        }
        $entityData = [];
        if($entity) {
            $entityData = $this->service->getEntityData($entity->id);
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => $generalErrors,
            'status' => $status,
            'data' =>  $entityData,
            'token' => $token,
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
        $token = null;
        try {
            $data['id'] = $this->getRequest()->getAttribute('id');
            $entity = $this->service->update($data);
            $status = true;
            $message = $this->translate(static::TITLE_UPDATE_SUCCESS);
        } catch (InvalidFormTokenException $e) {
            $errors[] = ['message' => $this->translate('Access denied. Please refresh the page and try again.')];
        } catch (ValidatorException $e) {
            foreach ($this->service->parseErrors() as $key => $error) {
                $errors[] = ['message' => $this->translate($error['message'])];
            }
            if(count($this->service->parseErrors()) > 0) {
                $token = $this->csrf()->getHiddenInputString();
            }
        } catch (\Exception $e) {
//            $this->logger->error('Update failed: ' . $e->getMessage(), ['exception' => $e]);
            $generalErrors[] = ['message' => $this->translate('An unexpected error occurred. Please try again.')];
            $token = $this->csrf()->getHiddenInputString();
        }

        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => $generalErrors,
            'status' => $status,
            'data' => $this->service->getEntityData($data['id']),
            'token' => $token
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function form(): Response
    {
        $id = $this->getRequest()->getAttribute('id');
        $model = null;
        $this->setGlobalVariable('pageTitle', static::TITLE_CREATE);
        $formTitle = static::TITLE_CREATE;
        if ($id) {
            $model = $this->service->getById($id);
            $title = $model->getId();
            $formEntityTitle = '#' . $model->getId();
            $reflectionClass = new \ReflectionClass($model::class);
            if(static::FORM_TITLE_ENTITY_IDENTIFIER !== NULL &&
                $reflectionClass->hasProperty(static::FORM_TITLE_ENTITY_IDENTIFIER)) {
                $property = static::FORM_TITLE_ENTITY_IDENTIFIER;
                if($model->$property !== null) {
                    $title = $model->$property;
                    $formEntityTitle = $model->$property;
                }
            }
            $formTitle = sprintf('%s %s', static::TITLE_UPDATE, $formEntityTitle);
            $this->setGlobalVariable('pageTitle', static::TITLE_UPDATE . $title);
            $formAction = sprintf('/%s/update/%s/', static::PATH, $id);
            $dataAction = 'update';
        } else {
            $formAction = sprintf('/%s/create/', static::PATH);
            $dataAction = 'create';
        }
        $path = sprintf('/%s/', static::PATH);
        if (strlen($this->tableViewConfig['adminPath'])) {
            $path = sprintf('/%s/%s/', $this->tableViewConfig['adminPath'], static::PATH);
        }

        return $this->respond('form', array_merge($this->formData, [
            'model' => $model,
            'path' => $path,
            'formTitle' => $formTitle,
            'formAction' => $formAction,
            'dataAction' => $dataAction,
        ]));
    }

    /**
     * @return Response
     */
    public function view(): Response
    {
        $this->setGlobalVariable('pageTitle', static::TITLE_VIEW);
        $hideFilters = [];
        $queryParams = $this->getRequest()->getQueryParams();
        if(isset($queryParams['hideFilters'])) {
            $hideFilters = $queryParams['hideFilters'];
        }
        $tableDecorator = new TableDecorator($this->template, $this->service->compileTableColumns(), $hideFilters);
        return $this->respond('view', [
            'tableFilters' => $tableDecorator->generateFiltersHtml(),
            'columnHeaders' => $tableDecorator->generateColumnHeadersForView(),
            'entityPath' => $this->tableViewConfig['entityPath'],
            'adminPath' => $this->tableViewConfig['adminPath'],
            'searchableColumns' => $this->service->getSearchableColumns(),
            'primaryKeyIdentifier' => $this->tableViewConfig['primaryKeyIdentifier'],
            'pageEntity' => static::TITLE_VIEW,
            'titleCreate' => static::TITLE_CREATE,
            'jsPage' => static::PATH,
            'createButton' => $this->tableViewConfig['createButton'],
            'exportButton' => (bool)$this->exporterFactory
        ]);
    }

    /**
     * @TODO relations
     *
     * @return Response
     */
    public function delete(): Response
    {
        $errors = [];
        $message = '';
        $generalErrors = [];
        try {
            // @TODO must be done better
//          if($this->service instanceof User) {
//            if($this->getSession()->getStorage()->offsetGet('user')->getId() === $this->getRequest()->getAttribute('id')) {
//                throw new \Exception('You cannot delete a user you are logged in as.');
//            }
//          }
            $data = json_decode($this->getRequest()->getBody(), true) ?? [];
            if (!$this->csrf()->validate($data)) {
                throw new InvalidFormTokenException();
            }
            $this->service->delete($this->getRequest()->getAttribute('id'));
            $message = $this->translate(static::TITLE_DELETE_SUCCESS);
            $status = true;
        } catch (InvalidFormTokenException $e) {
            $status = false;
            $generalErrors[] = ['message' => $this->translate('Access denied. Please refresh the page and try again.')];
        } catch (\Exception $e) {
            $status = false;
//            $this->logger->error('Delete failed: ' . $e->getMessage(), ['exception' => $e]);
            $generalErrors[]['message'] = $this->translate(static::TITLE_DELETE_ERROR);
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => $generalErrors,
            'status' => $status,
            'token' => $this->csrf()->getHiddenInputString(),
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    /**
     * @TODO relations
     *
     * @return Response
     */
    public function deleteBulk(): Response
    {
        $errors = [];
        $status = false;
        $generalErrors = [];
        try {
            $data = json_decode($this->getRequest()->getBody(), true) ?? [];
            if (!isset($data['ids'])) {
                throw new \Exception('No ids provided.');
            }
            if (!$this->csrf()->validate($data)) {
                throw new InvalidFormTokenException();
            }
            //@TODO must be implemented better
//            if($this->service instanceof User) {
//                if (in_array($this->getSession()->getStorage()->offsetGet('loggedIn')->getId(), $data['ids'])) {
//                    throw new \Exception('You cannot delete a user you are logged in as.');
//                }
//            }
            foreach ($data['ids'] as $id) {
                $this->service->delete($id);
            }
            $status = true;
            $message = $this->translate(static::TITLE_DELETE_SUCCESS);
        }  catch (InvalidFormTokenException $e) {
            $status = false;
            $generalErrors[] = ['message' => $this->translate('Access denied. Please refresh the page and try again.')];
        } catch (\Exception $e) {
//            $this->logger->error('Bulk delete failed: ' . $e->getMessage(), ['exception' => $e]);
            $message = $this->translate(static::TITLE_DELETE_ERROR);
            $generalErrors[]['message'] = $this->translate('An unexpected error occurred. Please try again.');
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message ?? '',
            'generalErrors' => $generalErrors,
            'status' => $status,
            'token' => $this->csrf()->getHiddenInputString(),
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function tableHandler()
    {
        $params = $this->getRequest()->getParsedBody();
        $search = ($params['search']) ?? false;
        $filter = [];
        if($search) {
            $search = filter_var($search, FILTER_SANITIZE_ADD_SLASHES);
        }
        $page = 1;
        if (isset($params['filter'])) {
            $filter = $params['filter'];
        }
        if (isset($filter['page'])) {
            $page = $filter['page'];
            unset($filter['page']);
        }
        $order = [];
        $filteredCount = ($filter || $search !== '') ?? true;
        if(isset($params['order'], $params['orderBy'])) {
            $order = ['order' => $params['order'], 'orderBy' => $params['orderBy']];
        }
        $totalCountFilter = [];
        if ($this->getSession()->getStorage()->offsetGet('loggedInRole') !== \Skeletor\User\Model\User::ROLE_ADMIN && $this->getSession()->getStorage()->offsetGet('tenantId')) {
            $filter[$this->service::TENANT_ID_FIELD_NAME] = $this->getSession()->getStorage()->offsetGet('tenantId');
            $totalCountFilter[$this->service::TENANT_ID_FIELD_NAME] = $this->getSession()->getStorage()->offsetGet('tenantId');
        }
        if (!isset($params['limit'])) {
            $params['limit'] = 10;
        }
        if (!isset($params['offset'])) {
            $params['offset'] = 0;
        }
        $totalCount = $this->service->getTotalCount(array_merge($totalCountFilter, $this->uncountableFilters));

        $maxPage = ceil($totalCount / $params['limit']);

        if((int)$page > $maxPage) {
            $page = (int)$maxPage;
            $params['offset'] = ($page - 1) * (int)$params['limit'];
        }
        if((int)$page < 1) {
            $page = 1;
            $params['offset'] = 0;
        }

        $data = $this->service->fetchTableData($search, $filter, $params['offset'], $params['limit'], $order, $this->uncountableFilters);

        if ($filteredCount) {
            $maxPage = ceil($totalCount / $params['limit']);
        }
        if($totalCount !== 0) {
            $bodyData = [
                'data' => $data['entities'],
                'filteredCount' => ($filteredCount) ? $totalCount : null,
                'totalCount' => $totalCount,
                'page' => (int) $page,
                'resultsFound' => count($data['entities']),
                'maxPage' => $maxPage,
                'countColumnData' => $data['countColumnData']
            ];
            $message = 'ok';
        } else {
            $bodyData = [
                'data' => [],
                'page' => 1,
                'maxPage' => 1,
                'filteredCount' => 0,
                'totalCount' => $totalCount,
                'resultsFound' => 0
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


    public function export(): Response
    {
        $params = $this->getRequest()->getParsedBody();
        $search = ($params['search']) ?? false;
        $filter = [];
        if($search) {
            $search = filter_var($search, FILTER_SANITIZE_ADD_SLASHES);
        }
        if (isset($params['filter'])) {
            $filter = $params['filter'];
            unset($filter['page']);
        }
        $order = [];
        if(isset($params['order'], $params['orderBy'])) {
            $order = ['order' => $params['order'], 'orderBy' => $params['orderBy']];
        }
        $totalCountFilter = [];
        if ($this->getSession()->getStorage()->offsetGet('loggedInRole') !== \Skeletor\User\Model\User::ROLE_ADMIN && $this->getSession()->getStorage()->offsetGet('tenantId')) {
//        var_dump($this->getSession()->getStorage()->offsetGet('tenantId'));
//        die();
            $filter['tenant'] = $this->getSession()->getStorage()->offsetGet('tenantId');
            $totalCountFilter['tenant'] = $this->getSession()->getStorage()->offsetGet('tenantId');
        }
        $params['limit'] = 10000;
        $params['offset'] = 0;

        try {
            $data = $this->service->fetchTableData($search, $filter, $params['offset'], $params['limit'], $order, $this->uncountableFilters);
            $formattedData = [];
            $columns = explode(',', $params['columns']);
            $formattedData[] = $columns;
            foreach ($data['entities'] as $entity) {
                $formattedEntity = [];
                foreach ($columns as $column) {
                    if(isset($entity['columns'][$column]['value'])) {
                        $formattedEntity[] = $entity['columns'][$column]['value'];
                        continue;
                    }
                    $formattedEntity[] = $entity['columns'][$column];
                }
                $formattedData[] = $formattedEntity;
            }
            $exporter = $this->exporterFactory->createExporter($params['exportType']);
            $exporter->export($formattedData);
        } catch (\Throwable $e) {
//            $this->logger->error($e->getMessage());
            $this->getResponse()->getBody()->write(json_encode([
                'error' => $this->translate('Error in exporting data. Please try again.'),
            ]));
            $this->getResponse()->getBody()->rewind();

            return $this->getResponse()->withHeader('Content-Type', 'application/json');
        }

        return $this->getResponse();
    }

    protected function makeTableService()
    {
        throw new \Exception('Child class must override this method to provide table service.');
    }

    public function getEntityData()
    {
        $this->getResponse()->getBody()->write(json_encode($this->service->getEntityData(
            $this->getRequest()->getAttribute('id')
        )));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse();
    }

    public function getEntities()
    {
        $this->getResponse()->getBody()->write(json_encode($this->service->getEntities()));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse();
    }
}