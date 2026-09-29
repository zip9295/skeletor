<?php

namespace Skeletor\Tag\Model;

use Doctrine\ORM\EntityManagerInterface;
use Skeletor\Image\Entity\Image;
use Skeletor\Image\Factory\ImageFactory;

class TagFactory
{
    public static function compileEntityForUpdate($data, $em)
    {
        $tag = $em->getRepository(\Skeletor\Tag\Entity\Tag::class)->find($data['id']);
        $stickerImage = null;
        if ($data['stickerImageId']) {
            $stickerImage = $em->getRepository(Image::class)->find($data['stickerImageId']);
        }
        unset($data['stickerImageId']);
        $tag->populateFromDto(new \Skeletor\Tag\Model\Tag(...$data));
        $tag->setImage($stickerImage);

        return $tag->getId();
    }

    public static function compileEntityForCreate($data, $em)
    {
        $tag = new \Skeletor\Tag\Entity\Tag();
        $data['id'] = null;
        $stickerImage = null;
        if($data['stickerImageId']) {
            $stickerImage = $em->getRepository(Image::class)->find($data['stickerImageId']);
        }
        unset($data['stickerImageId']);
        $tag->populateFromDto(new \Skeletor\Tag\Model\Tag(...$data));
        $tag->setImage($stickerImage);
        $em->persist($tag);

        return $tag->getId();
    }

    public static function make($itemData, EntityManagerInterface $em): \Skeletor\Tag\Model\Tag
    {
        if($itemData['stickerImage']) {
            $itemData['stickerImage'] = ImageFactory::make($em->getUnitOfWork()->getOriginalEntityData($itemData['stickerImage']), $em);
        }
        unset($itemData['stickerImageId']);

        return new \Skeletor\Tag\Model\Tag(...$itemData);
    }
}