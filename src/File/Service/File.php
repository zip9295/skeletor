<?php

namespace Skeletor\File\Service;

use Skeletor\File\Repository\FileRepository;
use Skeletor\Core\TableView\Service\TableView;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\User\Service\Session;

class File extends TableView
{

    public function __construct(
        FileRepository $repo,
        Session $user,
        Logger $logger,
        \Skeletor\File\Filter\File $filter,
        private Processor $processor,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repo, $user, $logger, $filter, activity: $activity);
    }

    public function getEntityData($id)
    {
        $file = $this->repo->getById($id);

        return [
            'id' => $file->id,
            'filename' => $file->filename,
            'mimeType' => $file->mimeType,
            'createdAt' => $file->createdAt->format('m.d.Y'),
            'updatedAt' => $file->updatedAt->format('m.d.Y'),
        ];
    }

    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $file) {
            $itemData = [
                'filename' =>  [
                    'value' => $file->filename,
                    'editColumn' => true,
                ],
                'id' => $file->id,
                'mimeType' => $file->mimeType,
                'createdAt' => $file->createdAt->format('d.m.Y'),
                'updatedAt' => $file->updatedAt->format('d.m.Y'),
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $file->id,
            ];
        }
        return $items;
    }

    public function compileTableColumns()
    {
        return [
            ['name' => 'filename', 'label' => 'Filename'],
            ['name' => 'mimeType', 'label' => 'Type'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at'],
        ];
    }

    public function create(array $data)
    {
        return $this->repo->create($this->processor->processUploadedFile($data['file']));
    }

    public function update(array $data)
    {
        if (isset($data['file']) && $data['file']?->getSize() !== 0) {
            $imageData = $this->processor->processUploadedFile($data['file']);
            $data['file'] = $imageData['filename'];
        }
        if (isset($data['file']) && $data['file'] === '' && $data['oldFile'] !== '') {
            $data['file'] = $data['oldFile'];
        }
        unset($data['oldFile']);
        $oldModel = $this->repo->getById($data['id']);
        $model = $this->repo->update($data);

        return $model;
    }

    public function createFromData($data)
    {
        return $this->repo->create($data);
    }

}