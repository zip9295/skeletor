<?php

namespace Skeletor\Blog\Controller;

use GuzzleHttp\Psr7\Response;
use Skeletor\Author\Service\Author;
use Skeletor\Blog\Service\Category;
use Skeletor\Blog\Service\Post;
use Skeletor\ContentEditor\Exceptions\BlockFilterNotFoundException;
use Skeletor\Core\Activity\Service\Activity;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Core\Validator\InvalidFormTokenException;
use Skeletor\Core\Validator\ValidatorException;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class PostController extends AjaxCrudController
{
    const TITLE_VIEW = "View posts";
    const TITLE_CREATE = "Create post";
    const TITLE_UPDATE = "Edit post: ";
    const TITLE_UPDATE_SUCCESS = "Post updated successfully.";
    const TITLE_CREATE_SUCCESS = "Post created successfully.";
    const TITLE_DELETE_SUCCESS = "Post deleted successfully.";
    const FORM_TITLE_ENTITY_IDENTIFIER = 'title';
    const PATH = 'post';

    public function __construct(
        private Post $postService,
        Session $session,
        Config $config,
        Flash $flash,
        Engine $template, Logger $logger,
        private Category $categoryService,
        private Author $authorService,
        private Activity $activityService
    )
    {
        parent::__construct($postService, $session, $config, $flash, $template, $logger);
    }

    public function create(): Response
    {
        $errors = [];
        $status = false;
        $message = '';
        $entity = $generalErrors = [];
        $data = $this->getRequest()->getParsedBody();
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
        } catch (\Throwable $e) {
//            $this->logger->error('Create failed: ' . $e->getMessage(), ['exception' => $e]);
            $generalErrors[] = ['message' => $this->translate('An unexpected error occurred. Please try again.')];
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => $generalErrors,
            'status' => $status,
            'data' =>  $entity ? ['id' => $entity->id, 'slug' => $entity->slug] : [],
            'token' =>  $this->csrf()->getHiddenInputString()
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
        } catch (BlockFilterNotFoundException $e) {
            $errors[] = ['message' => $this->translate($e->getMessage())];
        }
        catch (\Exception $e) {
//            $this->logger->error('Update failed: ' . $e->getMessage(), ['exception' => $e]);
            $generalErrors[] = ['message' => $this->translate('An unexpected error occurred. Please try again.')];
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => $errors,
            'message' => $message,
            'generalErrors' => $generalErrors,
            'status' => $status,
            'data' => $entity ? ['id' => $entity->id, 'slug' => $entity->slug] : [],
            'token' =>  $this->csrf()->getHiddenInputString()
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
        $initialContent = [];
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
            $formAction = sprintf('/%s/update/%s/', strtolower(static::PATH), $id);
            $dataAction = 'update';

            $status = ['status' => $model->status];
            if($model->publishAt) {
                $status['schedule'] = $model->publishAt->format('Y-m-d H:i:s');
            }
            $categories = [];
            if($model->mainCategory) {
                $categories[] = $model->mainCategory->id;
            }
            foreach($model->categories as $category) {
                $categories[] = $category->id;
            }
            $tags = [];
            foreach($model->tags as $tag) {
                $tags[] = ['id' => $tag->id, 'name' => $tag->title];
            }
            $initialContent = [
                'title' => $model->title,
                'slug' => $model->slug,
                'blocks' => $model->blockData,
                'categories' => $categories,
                'featuredImage' => ['id' => $model->featuredImage?->id, 'src' => $model->featuredImage?->filename],
                'status' => $status,
                'tags' => $tags,
                'excerpt' => $model->shortDescription,
                'authors' => $model->author->id ? [$model->author->id] : [],
                'seo' => [
                    'title' => $model->seoTitle,
                    'description' => $model->seoDescription,
                    'image' => ['id' => $model->seoImage?->id, 'src' => $model->seoImage?->filename]
                ],
                'revisions' => $this->getRevisionData($model)
            ];

        } else {
            $formAction = sprintf('/%s/create/', strtolower(static::PATH));
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
            'initialContent' => $initialContent,
            'categories' =>  $this->categoryService->getHierarchy(),
            'authors' => $this->authorService->getEntities()
        ]));
    }

    private function getRevisionData($model): array
    {
        $activities = $this->activityService->getEntities(
            [
                'entityId' => $model->id,
                'entityType' => $this->service->activityEntityType(),
                'action' => ['create', 'update']
            ],
            5,
            ['createdAt' => 'DESC'],
            1
        );
        $revisions = [];
        if($activities) {
            foreach($activities as $activity) {
                $hasChanges = false;
                if($activity->newData) {
                    $data = json_decode($activity->newData, true);
                    $revisionData = [
                        'id' => $activity->id,
                        'date' => $activity->createdAt->format('Y-m-d h:i:s'),
                        'author' => $activity->user->displayName,
                    ];
                    if(isset($data['blockData'])) {
                        foreach($data['blockData'] as &$blockData) {
                            if($blockData['additionalData'] === []) {
                                $blockData['additionalData'] = (object)[];
                            }
                        }
                        $revisionData['content']['blocks'] = $data['blockData'];
                        $hasChanges = true;
                    }
                    if(isset($data['title'])) {
                        $revisionData['content']['title'] = $data['title'];
                        $hasChanges = true;
                    }
                    if($hasChanges) {
                        $revisions[] = $revisionData;
                    }
                }
            }
        }
        return $revisions;
    }
}
