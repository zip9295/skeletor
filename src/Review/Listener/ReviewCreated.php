<?php

namespace Skeletor\Review\Listener;

use Skeletor\Core\Activity\Listener\Created as ActivityListener;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Entity\Entity;
use Skeletor\Notification\Model\NotificationTypes;
use Skeletor\Notification\Service\Notification;
use Skeletor\Review\Mapper\Review;

class ReviewCreated extends ActivityListener
{
    public function __construct(ActivityRepository $activity, private Notification $notificationService,
        private Review $reviewMapper)
    {
        parent::__construct($activity);
    }

    public function __invoke(object $event): void
    {
        $this->createNotifications($event->getData()['model']);
    }

    private function createNotifications($model)
    {
        if($model->getReplyTo() && $model->getVisitor()) {
            $notificationVisitor = $this->reviewMapper->fetchById($model->getReplyTo());
            if($notificationVisitor && isset($notificationVisitor['visitorId'])) {
                $this->notificationService->createReplyToNotification([
                    'content' => sprintf(
                        '%s has replied to your comment.',
                        $model->getVisitor()->getDisplayName() ?? ''
                    ),
                    'linkTo' => $model->getEntity()?->getUrl() ?
                        ($model->getEntity()->getUrl() . '?reviewId=' . $model->getReplyTo()) : '',
                    'entityId' => $model->getId(),
                    'entityType' => Entity::TYPE_REVIEW,
                    'notificationType' => NotificationTypes::REVIEW_REPLY_NOTIFICATION->value
                ], $notificationVisitor['visitorId'], false);
            }
        }
    }
}