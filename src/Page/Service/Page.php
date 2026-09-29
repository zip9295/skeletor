<?php

namespace Skeletor\Page\Service;

use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Filter\FilterInterface as Filter;
use Skeletor\Core\TableView\Repository\TableViewRepositoryInterface as Repository;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\Page\Repository\PageRepository;
use Skeletor\Tenant\Repository\TenantRepositoryInterface as TenantRepo;
use Skeletor\User\Service\Session;

class Page extends TableView
{
    public function __construct(
        PageRepository $repository,
        Session $userSession,
        Logger $logger,
        \Skeletor\Page\Filter\Page $filter,
        \Skeletor\Core\Activity\Service\Activity $activity,
        ?TenantRepo $tenant = null) {
        parent::__construct($repository, $userSession, $logger, $filter, $tenant, activity: $activity);
    }

    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $page) {
            $imgHtml = '';
            if ($page->featuredImage) {
                $image = $page->featuredImage->filename;
                $imageUrl = "/images" . $image;
                $imgHtml = '<img width="90px" src="'.$imageUrl.'" alt="category image">';
            }
            $itemData = [
                'id' => $page->id,
                'title' =>  [
                    'value' => $page->title,
                    'editColumn' => true,
                ],
                'slug' => $page->slug,
                'image' => $imgHtml,
                'status' => \Skeletor\Page\Entity\Page::getHrStatus($page->status),
                'createdAt' => $page->createdAt->format('d.m.Y'),
                'updatedAt' => $page->updatedAt->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $page->id,
            ];
        }
        return $items;
    }


    public function compileTableColumns(): array
    {
        return [
            ['name' => 'title', 'label' => 'Name'],
            ['name' => 'slug', 'label' => 'Slug'],
            ['name' => 'status', 'label' => 'Status'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at'],
        ];
    }
}