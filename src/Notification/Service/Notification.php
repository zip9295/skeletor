<?php

namespace Skeletor\Notification\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\TableView\Service\Table;
use Skeletor\Entity\Entity;
use Skeletor\Notification\Mapper\VisitorNotificationMeta;
use Skeletor\Notification\Model\NotificationTypes;
use Skeletor\Notification\Repository\NotificationRepository;
use Skeletor\Tenant\Repository\TenantRepositoryInterface;
use Skeletor\User\Service\Session;
use Skeletor\Visitor\Model\Visitor;

class Notification extends Table
{
    public function __construct(
        NotificationRepository $repo,
        Session $userSession,
        Logger $logger,
        private VisitorNotificationMeta $visitorNotificationMetaMapper,
        \Skeletor\Notification\Filter\Notification $filter,
        ?TenantRepositoryInterface $tenant = null
    ) {
        parent::__construct($repo, $userSession, $logger, $tenant, $filter);
    }

    public function fetchTableData($search, $filter, $offset, $limit, $order, $uncountableFilter = null)
    {
        $data = $this->repo->fetchTableData($search, $filter, $offset, $limit, $order, $uncountableFilter);
        if ($data['count'] === "0") {
            return [
                'count' => 0,
                'entities' => [],
            ];
        }
        $items = [];
        foreach ($data['entities'] as $notification) {
            $itemData = [
                'id' => $notification->getId(),
                'content' => $notification->getContent(),
                'linkTo' => $notification->getLinkTo(),
                'entityId' => $notification->getEntityId(),
                'entityType' => Entity::getHR($notification->getEntityType()),
                'notificationType' => NotificationTypes::from($notification->getNotificationType())->getHR(),
                'createdAt' => $notification->getCreatedAt()->format('d.m.Y'),
                'updatedAt' => $notification->getCreatedAt()->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $notification->getId(),
            ];
        }
        return [
            'count' => $data['count'],
            'entities' => $items,
        ];
    }

    public function compileTableColumns()
    {
        $columnDefinitions = [
            ['name' => 'id', 'label' => '#'],
            ['name' => 'content', 'label' => 'Content'],
            ['name' => 'linkTo', 'label' => 'Link'],
            ['name' => 'entityId', 'label' => 'Entity ID'],
            ['name' => 'entityType', 'label' => 'Entity Type'],
            ['name' => 'notificationType', 'label' => 'Notification Type', 'filterData' => NotificationTypes::getFilterData()],
            ['name' => 'createdAt', 'label' => 'Created at'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
        ];

        return $columnDefinitions;
    }

    public function doesNotificationExistForEntity($entityId, $entityType, $notificationType)
    {
        return $this->repo->doesNotificationExistForEntity($entityId, $entityType, $notificationType);
    }

    public function deleteByEntityForType($entityId, $entityType, $notificationType)
    {
        $this->repo->deleteByeEntityForType($entityId, $entityType, $notificationType);
    }

    public function getNonDismissedNotificationCountByVisitorId($visitorId)
    {
        return $this->visitorNotificationMetaMapper->getNonDismissedNotificationCountByVisitorId($visitorId);
    }

    public function getNonDismissedNotificationsForVisitor($visitorId, $offset, $limit)
    {
        $notifications = [];
        $notificationMeta = $this->visitorNotificationMetaMapper->fetchAll([
            'visitorId' => $visitorId, 'dismissed' => 0
        ], ['offset' => $offset, 'limit' => $limit], ['orderBy' => 'createdAt', 'dir' => 'desc']);
        foreach($notificationMeta as $notificationData) {
            $notifications[] = $this->repo->getById($notificationData['notificationId'])->toArray();
        }
        return $notifications;
    }

    public function dismissNotificationForVisitor($visitorId, $notificationId)
    {
        $notificationMetaData = $this->visitorNotificationMetaMapper->fetchAll([
            'visitorId' => $visitorId,
            'notificationId' => $notificationId
        ]);
        foreach($notificationMetaData as $notificationMeta) {
            $this->visitorNotificationMetaMapper->updateField(
                'dismissed',
                \Skeletor\Notification\Model\Notification::NOTIFICATION_DISMISSED,
                $notificationMeta['id']
            );
        }
    }

    public function create(array $data, $useCSRF = true)
    {
        if ($this->filter) {
            $data = $this->filter->filter($data, $useCSRF);
        }
        $model = $this->repo->create($data);

        return $model;
    }

    public function createReplyToNotification(array $data,  $visitorId, $useCSRF = true)
    {
        if ($this->filter) {
            $data = $this->filter->filter($data, $useCSRF);
        }
        $model = $this->repo->createReplyToNotification($data, $visitorId);
        return $model;
    }


    public function getLastNotificationTimestampForVisitor($visitorId)
    {
        return $this->visitorNotificationMetaMapper->getLastNotificationTimestampForVisitor($visitorId);
    }

    public function getNewNotificationCountAndTimestamp($visitorId, $timestamp)
    {
        return $this->visitorNotificationMetaMapper->getNewNotificationCountAndTimestamp($visitorId, $timestamp);
    }

    public function getNewNotificationsForVisitor($visitorId, $timestamp)
    {
        $newNotificationMeta = $this->visitorNotificationMetaMapper->getNewNotificationsForVisitor($visitorId, $timestamp);
        $notifications = [];
        if(count($newNotificationMeta) > 0) {
            $notifications['timestamp'] = $newNotificationMeta[0]['createdAt'];
        }
        foreach($newNotificationMeta as $newNotificationMetaData) {
            $notifications['entities'][] = $this->repo->getById($newNotificationMetaData['notificationId'])->toArray();
        }
        return $notifications;
    }
}