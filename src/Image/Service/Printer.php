<?php

namespace Skeletor\Image\Service;

use Skeletor\Blog\Model\Category;
use Skeletor\Blog\Model\Post;
use \Skeletor\Image\Entity\Image;

class Printer
{
    public static function getCropSrc(Image $image, $crop, $webp = false)
    {
        $pattern = explode('.', $image->filename);
        // remove text from crop name
        $crop = str_replace(['landscape_', 'portrait_', 'slider_'], '', $crop);
        $file = sprintf('%s_%s.%s', $pattern[0], $crop, $pattern[1]);
        if($webp) {
            $file = sprintf("%s_%s.webp", $pattern[0], $crop);
        }

        return '/images' . substr($file, strpos($file, 'images'));
    }

    public static function getDefaultCropData(Post|Category $post, $crop, $cropType = 'landscape', $webp = false)
    {
        if ($cropType === 'landscape') {
            if ($post instanceof Post && $post->getFeaturedLandscapeImage()) {
                return static::getCropSrc($post->getFeaturedLandscapeImage()->getImage(), $crop, $webp);
            }
            if ($post->getFeaturedImage() && $post->getFeaturedImage()->getOrientation() === 'landscape') {
                return static::getCropSrc($post->getFeaturedImage(), $crop, $webp);
            }
        }
        if ($cropType === 'portrait') {
            if ($post instanceof Post  && $post->getFeaturedPortraitImage()) {
                return static::getCropSrc($post->getFeaturedPortraitImage()->getImage(), $crop, $webp);
            }
            if ($post->getFeaturedImage() && $post->getFeaturedImage()->getOrientation() === 'portrait') {
                return static::getCropSrc($post->getFeaturedImage(), $crop, $webp);
            }
        }
        return '/images/dummyImages/cover' . $crop . '.jpg';
    }
}