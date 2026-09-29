<?php

namespace Skeletor\File\Controller;
use GuzzleHttp\Psr7\Response;
use Skeletor\Exporter\Contracts\ExporterFactoryInterface;
use Skeletor\File\Service\File;
use Skeletor\Core\Controller\AjaxCrudController;
use Laminas\Session\SessionManager as Session;
use Skeletor\Core\Config\Config;
use Skeletor\Core\Validator\ValidatorException;
use Tamtamchik\SimpleFlash\Flash;
use League\Plates\Engine;
use Psr\Log\LoggerInterface as Logger;

class FileController extends AjaxCrudController
{
    const TITLE_VIEW = "View file";
    const TITLE_CREATE = "Create new file";
    const TITLE_UPDATE = "Edit file: ";
    const TITLE_UPDATE_SUCCESS = "File updated successfully.";
    const TITLE_CREATE_SUCCESS = "File created successfully.";
    const TITLE_DELETE_SUCCESS = "File deleted successfully.";
    const PATH = 'file';

    protected $tableViewConfig = ['writePermissions' => true, 'useModal' => true, 'bulkEditable' => true];

    public function __construct(
        File $service, Session $session, Config $config, Flash $flash, Engine $template, Logger $logger, ExporterFactoryInterface $exporterFactory
    ) {
        parent::__construct($service, $session, $config, $flash, $template, $logger, $exporterFactory);
    }

    public function download(): Response
    {
        if (!$this->getSession()->getStorage()->offsetGet('loggedIn')) {
            return $this->redirect('/');
        }
        $file = $this->service->getById($this->getRequest()->getAttribute('id'));

        $response = $this->getResponse();
        $response = $response->withAddedHeader('Content-Type', $file->mimeType);
        $response = $response->withAddedHeader('Content-Transfer-Encoding', 'binary');
        $response = $response->withAddedHeader('Cache-Control', 'max-age=0');
        $response = $response->withAddedHeader('Content-Disposition', 'attachment;filename="'.basename($file->filename).'"');
        $response->getBody()->write(file_get_contents(APP_PATH . sprintf('/data/files/%s', $file->filename)));
        $response->getBody()->rewind();

        return $response;
    }

    public function create(): Response
    {
        $errors = [];
        $status = false;
        $message = '';
        $entity = $generalErrors = [];
        $data = $this->getRequest()->getParsedBody();
        if($this->getRequest()->getUploadedFiles() && isset($this->getRequest()->getUploadedFiles()['file'])) {
            $data['file'] = $this->getRequest()->getUploadedFiles()['file'];
        }
        try {
            $entity = $this->service->create($data);
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
}