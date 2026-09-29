<?php
namespace Skeletor\Core\Controller;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Activity\Service\Activity;
use Skeletor\Core\Filter\FilterInterface;
use Skeletor\Core\Repository\RepositoryInterface as Repository;
use Skeletor\Core\Validator\ValidatorException;
use Tamtamchik\SimpleFlash\Flash;

class CrudController extends Controller
{
    const TITLE_VIEW = "View entity";
    const TITLE_CREATE = "Create new entity";
    const TITLE_UPDATE = "Edit entity: ";
    const TITLE_UPDATE_SUCCESS = "Entity updated successfully.";
    const TITLE_CREATE_SUCCESS = "Entity created successfully.";
    const TITLE_DELETE_SUCCESS = "Entity deleted successfully.";
    const TITLE_DELETE_ERROR = "Could not delete entity";
    const PATH = 'entity';

    /**
     * @var Repository
     */
    private $repo;

    private $activity;

    protected $filter;

    /**
     * @param Repository $repo
     * @param Session $session
     * @param Config $config
     * @param Flash $flash
     * @param Engine $template
     * @param Logger $logger
     */
    public function __construct(
        Repository $repo, Session $session, Config $config, Flash $flash, Engine $template, Logger $logger,
        ?FilterInterface $filter = null, ?Activity $activity = null
    ) {
        parent::__construct($template, $config, $session, $flash, $logger);

        $this->repo = $repo;
        $this->activity = $activity;
        $this->filter = $filter;
    }

    public function getRepository()
    {
        return $this->repo;
    }

    public function index(): Response
    {
        return $this->respond('index', ['items' => $this->repo->fetchAll()]);
    }

    public function create(): Response
    {
        try {
            if ($this->filter) {
                $data = $this->filter->filter($this->getRequest());
            } else {
                $data = $this->getRequest()->getParsedBody();
            }
            $entity = $this->repo->create($data);
            if ($this->activity) {
                $this->activity->create('create', $this->repo->getById($entity->getId()), $this->getLoggedInUserId(), null);
            }
            $this->getFlash()->success(static::TITLE_CREATE_SUCCESS);
        } catch (ValidatorException $e) {
            $this->parseErrors();
            return $this->redirect(sprintf('/admin/%s/form/', static::PATH, $this->getRequest()->getAttribute('userId')));
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            die();
        }
        return $this->redirect(sprintf('/admin/%s/view/', static::PATH));
    }

    public function update(): Response
    {
        try {
            if ($this->filter) {
                $data = $this->filter->filter($this->getRequest());
            } else {
                $data = $this->getRequest()->getParsedBody();
            }
            $oldModel = $this->repo->getById((int) $this->getRequest()->getAttribute('id'));
            $entity = $this->repo->update($data);
            if ($this->activity) {
                $this->activity->create('update', $this->repo->getById($entity->getId()), $this->getLoggedInUserId(), $oldModel);
            }
            $this->getFlash()->success(static::TITLE_UPDATE_SUCCESS);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            die();
        }
        return $this->redirect(sprintf('/admin/%s/view/', static::PATH));
    }

    public function form(): Response
    {
        $id = (int) $this->getRequest()->getAttribute('id');
        $model = null;
        $this->setGlobalVariable('pageTitle', static::TITLE_CREATE);
        if ($id) {
            $model = $this->repo->getById($id);
            $title = $model->getId();
            if (method_exists($model, 'getName')) {
                $title = $model->getName();
            }
            $this->setGlobalVariable('pageTitle', static::TITLE_UPDATE . $title);
        }

        return $this->respond('form', [
            'model' => $model,
        ]);
    }

    /**
     * @return Response
     */
    public function view(): Response
    {
        $this->setGlobalVariable('pageTitle', static::TITLE_VIEW);

        return $this->respond('view', [
            'models' => $this->repo->fetchAll(),
        ]);
    }

    /**
     * @TODO relations
     *
     * @return Response
     */
    public function delete(): Response
    {
        try {
            $id = (int) $this->getRequest()->getAttribute('id');
            $model = $this->repo->getById($id);
            $this->repo->delete($id);
            if ($this->activity) {
                $this->activity->create('delete', $model, $this->getLoggedInUserId());
            }
            $this->getFlash()->success(static::TITLE_DELETE_SUCCESS);
        } catch (\Exception $e) {
            $this->getFlash()->error(static::TITLE_DELETE_ERROR .': '. $e->getMessage());
        }
        return $this->redirect(sprintf('/admin/%s/view/', static::PATH));
    }

    private function parseErrors()
    {
        foreach ($this->filter->getErrors() as $key => $messages) {
            foreach ($messages as $message) {
                $this->getFlash()->error($message);
            }
        }
    }
}