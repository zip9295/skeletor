<?php

namespace Skeletor\Tag\Service;

use Skeletor\Core\TableView\Service\TableView;
use Skeletor\User\Service\Session;
use Psr\Log\LoggerInterface;

class Tag extends TableView
{
    public function __construct(
        \Skeletor\Tag\Repository\TagRepoInterface $repo, Session $user, LoggerInterface $logger, ?\Skeletor\Tag\Filter\Tag $filter,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repo, $user, $logger, $filter, activity: $activity);
    }

    public function compileTableColumns()
    {
        return [
            ['name' => 'title', 'label' => 'Title'],
            ['name' => 'image', 'label' => 'Image'],
        ];
    }

    public function getEntityData($id)
    {
        return $this->formatEntityData($this->repo->getById($id));
    }

    public function prepareEntities($entities)
    {
        $items = [];
        /* @var \Skeletor\Tag\Model\Tag $tag */
        foreach ($entities as $tag) {
            $img = '';
            if($tag->getStickerImage()) {
                $img = sprintf('<img width="50px" src="/images/%s" />', $tag->getStickerImage()->getFilename());
            }
            $items[] = [
                'columns' => [
                    'id' => $tag->getId(),
                    'title' =>  [
                        'value' => $tag->getTitle(),
                        'editColumn' => true,
                    ],
                    'image' => $img
                ],
                'id' => $tag->getId(),
            ];
        }
        return $items;
    }

    protected function formatEntityData(\Skeletor\Tag\Model\Tag $tag)
    {
        $img = '';
        if($tag->getStickerImage()) {
//            $image = $this->imageRepo->getById($tag->getStickerImageId());
//            $img = sprintf('<img width="50px" src="/images/%s" />', $image->getFilename());
        }
        return [
            'id' => $tag->getId(),
            'title' => $tag->getTitle(),
            'image' => $img
        ];
    }
}