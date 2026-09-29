<?php

namespace Skeletor\Core\Activity\Controller;

use GuzzleHttp\Psr7\Response;
use Skeletor\Core\Config\Config;
use Laminas\Session\SessionManager as Session;
use League\Plates\Engine;
use Skeletor\Core\Activity\Service\Activity;
use Skeletor\Core\Controller\AjaxCrudController;
use Tamtamchik\SimpleFlash\Flash;
use Psr\Log\LoggerInterface as Logger;

class ActivityController extends AjaxCrudController
{
    const TITLE_VIEW = "View activity";
    const TITLE_CREATE = "Activity recorded.";
    const TITLE_UPDATE = "Activity updated.";
    const TITLE_UPDATE_SUCCESS = "Activity updated successfully.";
    const TITLE_CREATE_SUCCESS = "Activity recorded successfully.";
    const TITLE_DELETE_SUCCESS = "Activity deleted successfully.";
    const PATH = 'activity';

    protected $tableViewConfig = ['createButton' => false];

    public function __construct(
        Activity $activity,
        Session $session,
        Config $config,
        Flash $flash,
        Engine $template, Logger $logger,
    ) {
        parent::__construct($activity, $session, $config, $flash, $template, $logger);
    }

    /**
     * Override form() to decode JSON data for the detail view template.
     */
    public function form(): Response
    {
        $id = $this->getRequest()->getAttribute('id');
        if (!$id) {
            return $this->respond('form', ['model' => null, 'formTitle' => 'Activity', 'activityData' => null]);
        }

        $model = $this->service->getById($id);
        $formTitle = sprintf('Activity #%s — %s', $model->getId(), ucfirst($model->action));

        $activityData = [
            'action' => $model->action,
            'entityType' => $model->entityType,
            'entityId' => $model->entityId,
            'user' => $model->user ? $model->user->getEmail() : 'system',
            'createdAt' => $model->getCreatedAt()->format('d.m.Y H:i:s'),
            'ipAddress' => $model->ipAddress,
            'oldData' => $model->oldData ? json_decode($model->oldData, true) : null,
            'newData' => $model->newData ? json_decode($model->newData, true) : null,
            'diff' => $model->diff ? json_decode($model->diff, true) : null,
        ];

        return $this->respond('form', [
            'model' => $model,
            'formTitle' => $formTitle,
            'dataAction' => 'view',
            'activityData' => $activityData,
        ]);
    }

    /**
     * Restore entity state from a specific activity snapshot.
     */
    public function restore(): Response
    {
        $body = $this->getRequest()->getParsedBody();
        $activityId = (int) ($body['id'] ?? 0);
        $errors = [];
        $status = false;
        $message = '';

        if (!$activityId) {
            $errors[]['message'] = 'Activity ID is required.';
        } else {
            try {
                $result = $this->service->restoreFromActivity($activityId);
                $status = true;
                $message = sprintf('Successfully restored %s #%d.', $result['entityType'], $result['entityId']);
            } catch (\Throwable $e) {
                $errors[]['message'] = $e->getMessage();
            }
        }

        $this->getResponse()->getBody()->write(json_encode([
            'status' => $status,
            'message' => $message,
            'generalErrors' => $errors,
        ]));
        $this->getResponse()->getBody()->rewind();

        return $this->getResponse()->withHeader('Content-Type', 'application/json');
    }
}
