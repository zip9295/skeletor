<?php

namespace Skeletor\Blog\Controller;

use Skeletor\Blog\Service\Tag;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorException;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class TagController extends AjaxCrudController
{
    const TITLE_VIEW = "View tags";
    const TITLE_CREATE = "Create tag";
    const TITLE_UPDATE = "Edit tag: ";
    const TITLE_UPDATE_SUCCESS = "Tag updated successfully.";
    const TITLE_CREATE_SUCCESS = "Tag created successfully.";
    const TITLE_DELETE_SUCCESS = "Tag deleted successfully.";
    const FORM_TITLE_ENTITY_IDENTIFIER = 'title';
    const PATH = 'tag';

    public function __construct(private Tag $tagService, Session $session, Config $config, Flash $flash, Engine $template, Logger $logger)
    {
        parent::__construct($tagService, $session, $config, $flash, $template, $logger);
    }

    public function createOrGet()
    {
        $errors = [];
        $status = false;
        $message = '';
        $entity = $generalErrors = [];
        $data = $this->getRequest()->getParsedBody();
        if(isset($data['title'])) {
            $existingTag = $this->tagService->getEntities(['title' => trim($data['title'])]);
            if(count($existingTag) > 0) {
                return $this->respondJson(
                    $errors,
                    $message,
                    $generalErrors,
                    true,
                    ['id' => $existingTag[0]->id, 'title' => $existingTag[0]->title],
                    $this->csrf()->getHiddenInputString()
                );
            }
        }
        try {
            $data['seoTitle'] = $data['title']; //@todo how to handle this? its coming from the post form when creating tags on the flu
            $data['seoDescription'] = $data['title']; //@todo how to handle this? its coming from the post form when creating tags on the flu
            $entity = $this->tagService->create($data);
            $status = true;
            $message = $this->translate(static::TITLE_CREATE_SUCCESS);
        } catch (InvalidFormTokenException $e) {
            $errors[] = ['message' => $this->translate('Access denied. Please refresh the page and try again.')];
            $status = false;
        } catch (ValidatorException $e) {
            foreach ($this->service->parseErrors() as $key => $error) {
                $errors[] = ['message' => $this->translate($error['message'])];
            }
            $status = false;
        } catch (\Exception $e) {
//            $this->logger->error('Create failed: ' . $e->getMessage(), ['exception' => $e]);
            $generalErrors[] = ['message' => $this->translate('An unexpected error occurred. Please try again.')];
            $status = false;
        }
        return $this->respondJson(
            $errors,
            $message,
            $generalErrors,
            $status,
            $status ? ['id' => $entity->id, 'title' => $entity->title] : [],
            $this->csrf()->getHiddenInputString()
        );
    }

    private function respondJson(array $errors, string $message, array $generalErrors, bool $status, array $data, string $token) {
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => $generalErrors,
            'status' => $status,
            'data' =>  $data,
            'token' => $this->csrf()->getHiddenInputString()
        ]));
        $this->getResponse()->getBody()->rewind();
        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }
}
