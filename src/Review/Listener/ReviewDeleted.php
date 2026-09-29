<?php

namespace Skeletor\Review\Listener;

use Skeletor\Core\Activity\Listener\Deleted as ActivityListener;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Entity\Entity;
use Skeletor\Notification\Model\NotificationTypes;
use Skeletor\Notification\Service\Notification;
use Skeletor\Review\Mapper\ReviewLike;

class ReviewDeleted extends ActivityListener
{
    public function __construct(ActivityRepository $activity,
        private \Skeletor\Review\Mapper\Review $reviewMapper,
        private Notification $notificationService
    )
    {
        parent::__construct($activity);
    }

    public function __invoke(object $event): void
    {
        $this->deleteReplies($event->getData()['oldModel']);
    }

    private function deleteReplies($oldModel)
    {
        $replies = $this->reviewMapper->fetchAll(['replyTo' => $oldModel->getId()]);
        foreach($replies as $reply) {
            $this->notificationService->deleteByEntityForType(
                $reply['id'],
                Entity::TYPE_REVIEW,
                NotificationTypes::REVIEW_REPLY_NOTIFICATION->value
            );
            $this->reviewMapper->delete($reply['id']);
        }
    }
}