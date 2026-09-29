<?php

namespace Skeletor\Review\Controller;

use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager;
use League\Plates\Engine;
use Skeletor\Core\Controller\AjaxCrudController;
use Skeletor\Entity\Entity;
use Skeletor\Notification\Model\NotificationTypes;
use Skeletor\Notification\Service\Notification;
use Skeletor\Review\Model\Review;
use Skeletor\Review\Service\Review as Service;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class ReviewController extends AjaxCrudController
{
    const TITLE_VIEW = "View review";
    const TITLE_CREATE = "Create new review";
    const TITLE_UPDATE = "Edit review: ";
    const TITLE_UPDATE_SUCCESS = "Review updated successfully.";
    const TITLE_CREATE_SUCCESS = "Review created successfully.";
    const TITLE_DELETE_SUCCESS = "Review deleted successfully.";
    const PATH = 'review';
    protected $tableViewConfig = ['writePermissions' => true];

    public function __construct(
        Service $service, SessionManager $session, Config $config, Flash $flash, Engine $template, Logger $logger,
        private Notification $notificationService
    ) {
        parent::__construct($service, $session, $config, $flash, $template, $logger);
    }

    public function status()
    {
        $modelId = (int) $this->getRequest()->getQueryParams()['id'];
        $status = (int) $this->getRequest()->getQueryParams()['status'];
        $model = $this->service->getById($modelId);
        $generalErrors = [];
        if ($model->getStatus() === $status || !in_array($status, array_keys($model->getHrStatuses()))) {
            $generalErrors['message'] = 'Invalid status provided.';
            $message = 'Unable to change status.';
            $actionStatus = 0;
        } else {
            $message = 'Review unpublished successfully.';
            if ($status === 1) {
                $message = 'Review published successfully.';
            }
            $actionStatus = 1;
            $this->service->updateField('status', $status, $modelId);
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => [],
            'message' => $message,
            'generalErrors' => $generalErrors,
            'status' => $actionStatus,
            'data' => $model
        ]));
        $this->getResponse()->getBody()->rewind();
        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function bulkStatus()
    {
        $postData = $this->getRequest()->getParsedBody();
        $ids = $postData['ids'];
        $status = (int) $postData['status'];
        $message = '';
        $generalErrors = [];
        // Was Blog\Model\Post::getHrStatuses() -- a copy-paste from the post controller, and
        // wrong twice over: there is no Blog\Model namespace, so the call fatalled, and post
        // statuses are a different set from review ones (a review's NEW is 0, which no post
        // status uses, while 3 and 4 are posts-only). Reviews are still Model-backed; this
        // package has no Entity yet.
        if(!array_key_exists($status, \Skeletor\Review\Model\Review::getHrStatuses())) {
            $generalErrors['message'] = 'Invalid status provided.';
        }
        try {
            $this->service->bulkStatus($ids, $status);
            $message = 'Successfully changed the status.';
        } catch(\Exception $e) {
            $generalErrors['message'] = 'An unexpected error occurred.';
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => [],
            'message' => $message,
            'generalErrors' => $generalErrors
        ]));
        $this->getResponse()->getBody()->rewind();
        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function getReply()
    {
        $id = $this->getRequest()->getAttribute('id');
        $replies = null;
        if($id) {
            $replies = $this->service->getEntities(['replyTo' => $id]);
        }
        $repliesArray = [];
        if($replies) {
            foreach($replies as $reply) {
                $repliesArray[] = $reply->toArray();
            }
        }
        $this->getResponse()->getBody()->write(json_encode([
            'reply' => $repliesArray
        ]));
        $this->getResponse()->getBody()->rewind();
        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function deleteReply()
    {
        try {
            $generalErrors = [];
            $message = '';
            $postData = $this->getRequest()->getParsedBody();
            if (isset($postData['id'])) {
                $toBeDeleted = $this->service->getEntities(['replyTo' => $postData['id']]);
                $this->service->deleteByReplyTo($postData['id']);
                foreach($toBeDeleted as $entityToDelete) {
                    $this->notificationService->deleteByEntityForType(
                        $entityToDelete->getId(),
                        Entity::TYPE_REVIEW,
                        NotificationTypes::REVIEW_REPLY_NOTIFICATION->value
                    );
                }
            }
            $message = 'Successfully deleted the reply.';
        } catch(\Exception $e) {
            $generalErrors['message'] = 'An unexpected error occurred while deleting the reply.';
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => [],
            'message' => $message,
            'generalErrors' => $generalErrors
        ]));
        $this->getResponse()->getBody()->rewind();
        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function createReply()
    {
        try {
            $generalErrors = [];
            $message = '';
            $postData = $this->getRequest()->getParsedBody();
            if (isset($postData['id'], $postData['content'], $postData['entityId'], $postData['entityType'],
                $postData['visitorId'])) {
                $this->service->create([
                    'body' => htmlspecialchars(trim($postData['content'])),
                    'replyTo' => $postData['id'],
                    'entityId' => $postData['entityId'],
                    'entityType' => $postData['entityType'],
                    'status' => Review::STATUS_PUBLISHED,
                    'visitorId' => $postData['visitorId'],
                    'rating' => 0,
                    'email' => '',
                    'likeCount' => 0
                ], false);
            }
            $message = 'Successfully created the reply.';
        } catch(\Exception $e) {
            $generalErrors['message'] = 'An unexpected error occurred while creating the reply.';
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => [],
            'message' => $message,
            'generalErrors' => $generalErrors
        ]));
        $this->getResponse()->getBody()->rewind();
        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }

    public function updateReply()
    {
        try {
            $generalErrors = [];
            $message = '';
            $postData = $this->getRequest()->getParsedBody();
            if (isset($postData['id'], $postData['content'])) {
                $replies = $this->service->getEntities(['replyTo' => $postData['id']]);
                foreach($replies as $reply) {
                    $this->service->updateField('body', $postData['content'], $reply->getId());
                }
            }
            $message = 'Successfully updated the reply.';
        } catch(\Exception $e) {
            $generalErrors['message'] = 'An unexpected error occurred while updating the reply.';
        }
        $this->getResponse()->getBody()->write(json_encode([
            'errors' => [],
            'message' => $message,
            'generalErrors' => $generalErrors
        ]));
        $this->getResponse()->getBody()->rewind();
        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }


}