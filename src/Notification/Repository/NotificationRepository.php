<?php

namespace Skeletor\Notification\Repository;

use League\Event\EventDispatcher;
use Skeletor\Core\Model\Model;
use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Notification\Mapper\Notification;
use Skeletor\Notification\Mapper\VisitorNotificationMeta;
use Skeletor\Visitor\Model\Visitor;
use Skeletor\Visitor\Repository\VisitorReadRepository;

class NotificationRepository extends TableViewRepository
{

    public function __construct(
        Notification $mapper,
        private \DateTime $dt,
        private VisitorReadRepository $visitorReadRepo,
        private VisitorNotificationMeta $visitorNotificationMeta,
        ?EventDispatcher $dispatcher = null,
    )
    {
        parent::__construct($mapper, $dispatcher);
    }


    public function make($itemData): Model
    {
        $data = [];
        foreach ($itemData as $name => $value) {
            if (in_array($name, ['createdAt', 'updatedAt'])) {
                $data[$name] = null;
                if ($value) {
                    if (strtotime($value)) {
                        $dt = clone $this->dt;
                        $dt->setTimestamp(strtotime($value));
                        $data[$name] = $dt;
                    } else {
                        $data[$name] = null;
                    }
                }
            } else {
                $data[$name] = $value;
            }
        }

        if (!isset($data['createdAt'])) {
            $data['createdAt'] = null;
        }
        if (!isset($data['updatedAt'])) {
            $data['updatedAt'] = null;
        }

        return new \Skeletor\Notification\Model\Notification(...$data);
    }


    protected function beforeSave($data)
    {
        return $data;
    }

    protected function afterCreate($data, $model)
    {
        if ($this->event && $this->dispatcher) {
            $this->dispatcher->dispatch(new $this->event($this->event::CREATED, [
                'data' => $data,
                'model' => $model
            ]));
        }
        $visitors = $this->visitorReadRepo->fetchAll(['isActive' => \Skeletor\Visitor\Model\Visitor::STATUS_ACTIVE]);
        foreach ($visitors as $visitor) {
            $this->visitorNotificationMeta->insert([
                'visitorId' => $visitor->getId(),
                'notificationId' => $model->getId(),
                'dismissed' => \Skeletor\Notification\Model\Notification::NOTIFICATION_NOT_DISMISSED
            ]);
        }
        return $model;
    }

    public function getSearchableColumns(): array
    {
        return ['content', 'linkTo'];
    }

    public function doesNotificationExistForEntity($entityId, $entityType, $notificationType)
    {
        return $this->mapper->doesNotificationExistForEntity($entityId, $entityType, $notificationType);
    }

    public function deleteByeEntityForType($entityId, $entityType, $notificationType)
    {
        $this->mapper->deleteByeEntityForType($entityId, $entityType, $notificationType);
    }

    public function createReplyToNotification($data, $visitorId)
    {
        try {
            $entityId = $this->mapper->insert($data);
            $model = $this->getById($entityId);
            $this->visitorNotificationMeta->insert([
                'visitorId' => $visitorId,
                'notificationId' => $model->getId(),
                'dismissed' => \Skeletor\Notification\Model\Notification::NOTIFICATION_NOT_DISMISSED
            ]);
        } catch (\Exception $e) {
            if ($this->mapper->inTransaction()) {
                $this->mapper->rollBackTransaction();
            }
            throw $e;
        }
        return $model;
    }
}