<?php

namespace Skeletor\Notification\Model;

enum NotificationTypes: int
{
    case NEW_VIDEO_NOTIFICATION = 1;
    case NEW_BLOG_POST_NOTIFICATION = 2;
    case REVIEW_REPLY_NOTIFICATION = 3;

    const NEW_VIDEO_NOTIFICATION_LABEL = 'New Video Post';
    const NEW_BLOG_POST_NOTIFICATION_LABEL = 'New Blog Post';
    const REVIEW_REPLY_NOTIFICATION_LABEL = 'Review Reply';


    public function getHR(): string
    {
        return match($this) {
            self::NEW_VIDEO_NOTIFICATION => self::NEW_VIDEO_NOTIFICATION_LABEL,
            self::NEW_BLOG_POST_NOTIFICATION => self::NEW_BLOG_POST_NOTIFICATION_LABEL,
            self::REVIEW_REPLY_NOTIFICATION => self::REVIEW_REPLY_NOTIFICATION_LABEL
        };
    }

    public static function getFilterData(): array
    {
        return [
            self::NEW_VIDEO_NOTIFICATION->value => self::NEW_VIDEO_NOTIFICATION_LABEL,
            self::NEW_BLOG_POST_NOTIFICATION->value => self::NEW_BLOG_POST_NOTIFICATION_LABEL,
            self::REVIEW_REPLY_NOTIFICATION->value => self::REVIEW_REPLY_NOTIFICATION_LABEL
        ];
    }
}