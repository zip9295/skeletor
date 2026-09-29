<?php

namespace Skeletor\Core\Service;

use Skeletor\Blog\Repository\PostReadRepository;
use Skeletor\Entity\Entity;
use Skeletor\Image\Service\Image;

class BlocksParser
{
    public function __construct(private Image $image) {}

    public function parse(array $blocksData)
    {
        $blocks = [];
        foreach ($blocksData as $blockData) {
            $key = array_keys($blockData)[0];
            $viewControlPerRole = isset($blockData['viewControlPerRole']) ? $blockData['viewControlPerRole'] : null;
            switch ($key) {
                case 'textEditor':
                    $block = [
                        'type' => $key,
                        'value' => $blockData[$key],
                    ];
                    break;

                case 'embed':
                    $block = [
                        'type' => $key,
                        'value' => $blockData[$key],
                    ];

                    break;

                case 'image':
                    /* @var \Skeletor\Image\Model\Image $image */
                    $image = $this->image->getById($blockData[$key]);
                    $block = [
                        'type' => $key,
                        'imageId' => $image->getId(),
                        'filename' => $image->getFilename(),
                        'model' => serialize($image)
                    ];

                    break;

                case 'gallery':
                    $images = [];
                    foreach ($blockData[$key] as $imageId) {
                        $image = $this->image->getById($imageId);
                        $images[] = [
                            'imageId' => $image->getId(),
                            'filename' => $image->getFilename(),
                            'model' => serialize($image)
                        ];
                    }
                    $block = [
                        'type' => $key,
                        'images' => $images
                    ];
                    break;
                case 'entityList':
                    if (isset($blockData['entityList'])) {
                        $block = [
                            'type' => $key,
                            'title' => $blockData['entityList']['title'],
                            'viewType' => (int)$blockData['entityList']['viewType'],
                            'entityType' => (int)$blockData['entityList']['entityType'],
                            'entities' => []
                        ];
                        $entities = [];
                        if(isset($blockData['entityList']['subEntityType']) &&
                            $blockData['entityList']['subEntityType'] !== '-1') {
                            $block['subEntityType'] = (int)$blockData['entityList']['subEntityType'];
                        }
                        if (isset($blockData['entityList']['tag']['new'])) {
                            $entities = $blockData['entityList']['tag']['new'];
                        }
                        if (isset($blockData['entityList']['category']['new'])) {
                            $entities = $blockData['entityList']['category']['new'];
                        }
                        if($blockData['entityList']['orderBy'] !== '-1') {
                            $block['orderBy'] = $blockData['entityList']['orderBy'];
                        }
                        if($blockData['entityList']['flag'] !== '-1') {
                            $block['flag'] = $blockData['entityList']['flag'];
                        }
                        foreach ($entities as $entityName => $entityId) {
                            $block['entities'][][$entityName] = $entityId;
                        }
                    }
                    break;
                case 'banner':
                    if (isset($blockData['banner'])) {
                        $block = [
                            'type' => $key,
                            'title' => $blockData['banner']['title'],
                            'linkTo' => $blockData['banner']['linkTo'],
                            'embed' => $blockData['banner']['embed'],
                            'buttons' => []
                        ];
                        if(isset($blockData['banner']['description'])) {
                            $block['description'] = $blockData['banner']['description'];
                        }
                        if (isset($blockData['banner']['landscapeImage'])) {
                            $image = $this->image->getById($blockData['banner']['landscapeImage']);
                            $block['landscapeImage'] = [
                                'imageId' => $image->getId(),
                                'filename' => $image->getFilename(),
                                'model' => serialize($image)
                            ];
                        }
                        if (isset($blockData['banner']['portraitImage'])) {
                            $image = $this->image->getById($blockData['banner']['portraitImage']);
                            $block['portraitImage'] = [
                                'imageId' => $image->getId(),
                                'filename' => $image->getFilename(),
                                'model' => serialize($image)
                            ];
                        }
                        if (isset($blockData['banner']['buttons'])) {
                            foreach ($blockData['banner']['buttons'] as $button) {
                                $block['buttons'][] = $button;
                            }
                        }
                    }
                    break;
                case 'personList':
                    if (isset($blockData['personList'])) {
                        $block = $blockData['personList'];
                        $persons = [];
                        if (isset($block['persons'])) {
                            foreach ($block['persons'] as $personKey => $person) {
                                $personData = $person;
                                if (isset($person['image'])) {
                                    $image = $this->image->getById($person['image']);
                                    $personData['image'] = [
                                        'imageId' => $image->getId(),
                                        'filename' => $image->getFilename(),
                                        'model' => serialize($image)
                                    ];
                                }
                                $persons[] = $personData;
                            }
                        }
                        $block['persons'] = $persons;
                        $block['type'] = $key;
                    }
                    break;
                case 'slider':
                    if (isset($blockData['slider'], $blockData['slider']['slides'])) {
                        $block = [
                            'type' => $key,
                            'slides' => []
                        ];
                        foreach ($blockData['slider']['slides'] as $slide) {
                            $slideData = [
                                'title' => $slide['title'],
                                'linkTo' => $slide['linkTo'],
                                'embed' => $slide['embed'],
                                'buttons' => []
                            ];
                            if (isset($slide['landscapeImage'])) {
                                $image = $this->image->getById($slide['landscapeImage']);
                                $slideData['landscapeImage'] = [
                                    'imageId' => $image->getId(),
                                    'filename' => $image->getFilename(),
                                    'model' => serialize($image)
                                ];
                            }
                            if (isset($slide['portraitImage'])) {
                                $image = $this->image->getById($slide['portraitImage']);
                                $slideData['portraitImage'] = [
                                    'imageId' => $image->getId(),
                                    'filename' => $image->getFilename(),
                                    'model' => serialize($image)
                                ];
                            }
                            if (isset($slide['buttons'])) {
                                foreach ($slide['buttons'] as $button) {
                                    $slideData['buttons'][] = $button;
                                }
                            }
                            $block['slides'][] = $slideData;
                        }
                    }
                    break;
                case 'quote':
                    if (isset($blockData['quote'])) {
                        $block = [
                            'type' => $key
                        ];
                        if(isset($blockData['quote']['text'])) {
                            $block['text'] = $blockData['quote']['text'];
                        }
                        if(isset($blockData['quote']['signature'])) {
                            $block['signature'] = $blockData['quote']['signature'];
                        }
                    }
                    break;
                case 'contentList':
                    if (isset($blockData['contentList'])) {
                        $block = [
                            'type' => $key,
                            'viewType' => $blockData['contentList']['viewType'],
                            'entities' => []
                        ];
                        if (isset($blockData['contentList']['entities'])) {
                            foreach ($blockData['contentList']['entities'] as $entity) {
                                $block['entities'][] = [
                                    'title' => $entity['title'],
                                    'content' => $entity['content']
                                ];
                            }
                        }
                    }
                    break;
                case 'heading':
                    if(isset($blockData['heading'])) {
                        $block = [
                            'type' => $key,
                            'headingType' => $blockData['heading']['headingType'] ?? '',
                            'value' => $blockData['heading']['value'] ?? ''
                        ];
                    }
                    break;
                case 'courseList':
                    if(isset($blockData['courseList'])) {
                        $block = [
                            'type' => $key
                        ];
                    }
                    break;
                case 'homeIntro':
                    if(isset($blockData['homeIntro'])) {
                        $block = [
                            'type' => $key,
                            'title' => $blockData['homeIntro']['title'] ?? '',
                            'subtitle' => $blockData['homeIntro']['subtitle'] ?? '',
                        ];
                    }
                    break;
                case 'features':
                    if (isset($blockData['features'])) {
                        $features = [];
                        foreach ($blockData['features'] as $featureKey => $feature) {
                            $featureData = $feature;
                            if (isset($feature['image'])) {
                                $image = $this->image->getById($feature['image']);
                                $featureData['image'] = [
                                    'imageId' => $image->getId(),
                                    'filename' => $image->getFilename(),
                                    'model' => serialize($image)
                                ];
                            }
                            $features[] = $featureData;
                        }
                        $block = [
                            'features' => $features,
                            'type' => $key
                        ];
                    }
                    break;
                case 'howItWorks':
                    if(isset($blockData['howItWorks'])) {
                        $block = [
                            'type' => $key
                        ];
                    }
                    break;
                case 'signupForm':
                    if(isset($blockData['signupForm'])) {
                        $block = [
                            'type' => $key
                        ];
                    }
                    break;
                case 'weOffer':
                    if(isset($blockData['weOffer'])) {
                        $block = [
                            'type' => $key
                        ];
                    }
                    break;
                case 'testimonials':
                    if(isset($blockData['testimonials'])) {
                        $block = [
                            'type' => $key
                        ];
                    }
                    break;
                case 'facebookSupport':
                    if(isset($blockData['facebookSupport'])) {
                        $block = [
                            'type' => $key
                        ];
                    }
                    break;

            }
            if (isset($block)) {
                $block['viewControlPerRole'] = $viewControlPerRole;
                $blocks[] = $block;
            }
        }
        return $blocks;
    }

    public function parse2(array $blocks)
    {
        $parsed = [];
        foreach ($blocks as $block) {
            switch ($block['type']) {
                case 'textEditor':
                case 'embed':
                    break;

                case 'image':
                    $block['model'] = $this->image->getById($block['imageId']);
//                    unset($block['filename']);
//                    unset($block['imageId']);

                    break;

                case 'banner':
                    $block['landscapeImage'] = $this->image->getById($block['landscapeImage']->imageId);
                    $block['portraitImage'] = $this->image->getById($block['portraitImage']->imageId);

                    break;

                case 'slider':
                    $slides = [];
                    foreach ($block['slides'] as $slide) {
                        if (isset($slide->landscapeImage)) {
                            $slide->landscapeImage = $this->image->getById($slide->landscapeImage->imageId);
                        }
                        if (isset($slide->portraitImage)) {
                            $slide->portraitImage = $this->image->getById($slide->portraitImage->imageId);
                        }
                        $slides[] = $slide;
                    }
                    $block['slides'] = $slides;
                    break;

                case 'personList':
                    $persons = [];
                    foreach ($block['persons'] as $personData) {
                        if (isset($personData->image)) {
                            $personData->image = $this->image->getById($personData->image->imageId);
                        }
                        $persons[] = $personData;
                    }
                    $block['persons'] = $persons;
                    break;

                case 'entityList':
                    $modelIds = [];
                    $filter = [];
                    foreach ($block['entities'] as $entityData) {
                        switch ($block['entityType']) {
                            case Entity::TYPE_CATEGORY:
                                $filter['categoryId'][] = array_values((array) $entityData)[0];
                                break;
                        }
                    }
                    foreach ($this->post->getFilteredPublishedEntities($filter) as $postId) {
                        var_dump($postId);
                        // add post ids to block
                        die();
                    }

                    break;

                case 'gallery':
                    $images = [];
                    foreach ($block['images'] as $imageData) {
                        $images[] = $this->image->getById($imageData->imageId);
                    }
                    $block['images'] = $images;

                    break;
            }
            $parsed[] = $block;
        }

        return $parsed;
    }
}