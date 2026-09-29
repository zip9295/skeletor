<?php

namespace Skeletor\Core\Service;

use League\Plates\Engine;
use Skeletor\Blog\Repository\PostReadRepository;
use Skeletor\Blog\Service\PostRead;
use Skeletor\Blog\Service\VideoRead;
use Skeletor\Entity\Entity;
use Skeletor\Image\Service\Image;

class BlocksView
{
    public function __construct(private Image $image, private Engine $template, private VideoRead $videoRead,
        private PostRead $postRead) {}

    public function parse(array $blocksData)
    {
        $blocks = [];
        foreach ($blocksData as $blockData) {
            $key = $blockData['type'];
            $template = sprintf('blocks/%s', $key);
            switch ($key) {
                case 'textEditor':
                    $tplData = ['value' => $blockData['value']];

                    break;

                case 'embed':
                    $tplData = ['value' => $blockData['value']];

                    break;

                case 'image':
                    $image = unserialize($blockData['model']);
                    $tplData = ['image' => $image];

                    break;

                case 'gallery':
                    $images = [];
                    foreach ($blockData['images'] as $imageData) {
                        $images[] = unserialize($imageData->model);
                    }
                    $tplData = ['images' => $images];

                    break;

                case 'entityList':
                    $limit = 10;
                    $filter = [];
                    if($blockData['viewType'] === -1) {
                        $blockData['viewType'] = 1;
                    }
                    if ($blockData['viewType'] === 1) {
                        $template .= 'Slider';
                    } elseif ($blockData['viewType'] === 2) {
                        $template .= 'Grid';
                        $limit = 9;
                    }
                    $order = ['orderBy' => 'createdAt', 'dir' => 'desc'];
                    $entities = [];
                    $service = $this->getServiceByType($blockData['entityType']);
                    if(!$service) {
                        $block['entities'] = [];
                        $tplData = ['blockData' => $blockData];
                        break;
                    }
                    foreach ($blockData['entities'] as $entityData) {
                        switch ($blockData['subEntityType']) {
                            case Entity::TYPE_CATEGORY:
                                $filter['categoryId'][] = array_values((array) $entityData)[0];
                                break;
                            case Entity::TYPE_TAG:
                                $filter['tagId'][] = array_values((array) $entityData)[0];
                                break;
                        }
                        if(isset($blockData['orderBy'])) {
                            $order = ['orderBy' => $blockData['orderBy'], 'dir' => 'desc'];
                        }
                        if(isset($blockData['flag'])) {
                            $filter['flags'][$blockData['flag']] = 1;
                        }
                        $models = [];
                        if(is_object($service) && method_exists($service, 'getFilteredPublishedEntities')) {
                            foreach ($service->getFilteredPublishedEntities($filter, false, $order, $limit) as $post) {
                                $models[] = $post;
                            }
                        }
                        $entity = [
                            'title' => array_keys((array) $entityData)[0],
                            'items' => $models
                        ];
                        $entities[] = $entity;
                        $filter = [];
                    }
                    $blockData['entities'] = $entities;
                    $tplData = ['blockData' => $blockData];

                    break;
                case 'banner':
                    if(isset($blockData['landscapeImage'])) {
                        $blockData['landscapeImage'] = unserialize($blockData['landscapeImage']->model);
                    }
                    if(isset($blockData['portraitImage'])) {
                        $blockData['portraitImage'] = unserialize($blockData['portraitImage']->model);
                    }
                    $tplData = ['data' => $blockData];

                    break;
                case 'personList':
                    $persons = [];
                    foreach ($blockData['persons'] as $person) {
                        if(isset($person->image)) {
                            $person->image = unserialize($person->image->model);
                            $persons[] = $person;
                        }
                    }
                    $blockData['persons'] = $persons;
                    $tplData = ['data' => $blockData];

                    break;
                case 'slider':
                    $slides = [];
                    foreach ($blockData['slides'] as $slide) {
                        $landscapeImage = null;
                        if (isset($slide->landscapeImage->model)) {
                            $landscapeImage = unserialize($slide->landscapeImage->model);
                        }
                        $slide->landscapeImage = $landscapeImage;
                        $portraitImage = null;
                        if (isset($slide->portraitImage->model)) {
                            $portraitImage = unserialize($slide->portraitImage->model);
                        }
                        $slide->portraitImage = $portraitImage;
                        $slides[] = $slide;
                    }

                    $tplData = ['slides' => $slides];
                    break;
                case 'quote':
                    $tplData = ['data' => $blockData];
                    break;
                case 'contentList':
                    $tplData = ['data' => $blockData];
                    break;
                case 'heading':
                    $tplData = ['data' => $blockData];
                    break;
            }
            if ($this->template->exists($template)) {
                $html = $this->template->render($template, $tplData);
            } else {
                $tpl = 'defaultTheme::' . $template;
                $html = $this->template->render($tpl, $tplData);
            }
            $blocks[] = $html;
        }
        return $blocks;
    }

    private function getServiceByType($type): PostRead|VideoRead|null {
        return match($type) {
            Entity::TYPE_POST => $this->postRead,
            Entity::TYPE_VIDEO_POST => $this->videoRead,
            default => null
        };
    }

}