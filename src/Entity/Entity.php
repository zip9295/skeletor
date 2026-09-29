<?php
namespace Skeletor\Entity;

class Entity
{
    const TYPE_POST = 1;
    const TYPE_VIDEO_POST = 2;
    const TYPE_CATEGORY = 3;
    const TYPE_TAG = 4;
    const TYPE_PAGE = 5;
    const TYPE_AUTHOR = 6;
    const TYPE_REVIEW = 7;
    const TYPE_TENANT = 8;
    const TYPE_CLIENT = 9;
    const TYPE_BANK = 10;
    const TYPE_USER = 11;

    public static function getHR($type): string
    {
        return match($type) {
            self::TYPE_POST => 'Blog Post',
            self::TYPE_VIDEO_POST => 'Video Post',
            self::TYPE_CATEGORY => 'Category',
            self::TYPE_TAG => 'Tag',
            self::TYPE_PAGE => 'Page',
            self::TYPE_AUTHOR => 'Author',
            self::TYPE_REVIEW => 'Review',
            self::TYPE_TENANT => 'Tenant',
            self::TYPE_CLIENT => 'Client',
            default => 'Entity Type'
        };
    }

    public static function classToType($class): string
    {
        $parts = explode('\\', $class);

        return match($parts[count($parts)-1]) {
            'Post' => self::TYPE_POST,
            'Video' => self::TYPE_VIDEO_POST,
            'Category' => self::TYPE_CATEGORY,
            'Tag' => self::TYPE_TAG,
            'Page' => self::TYPE_PAGE,
            'Author' => self::TYPE_AUTHOR,
            'Review' => self::TYPE_REVIEW,
            'Tenant' => self::TYPE_TENANT,
            'Client' => self::TYPE_CLIENT,
            default => 'No recognized type'
        };
    }
}
