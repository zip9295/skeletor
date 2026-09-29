<?php
namespace Skeletor\Image\Service;

use GuzzleHttp\Psr7\UploadedFile;
use Psr\Log\LoggerInterface as Logger;
use Skeletor\Core\Activity\Repository\ActivityRepository;
use Skeletor\Core\TableView\Service\TableView;
use Skeletor\Image\Repository\ImageRepository;
use Skeletor\User\Service\Session;

class Image extends TableView
{
    protected $processor;

    /**
     * @param ImageRepository $repo
     * @param Session $userSession
     * @param Logger $logger
     * @param ActivityRepository $activity
     * @param Image $imageService
     */
    public function __construct(
        ImageRepository $repo, Session $userSession, Logger $logger, Processor $processor,
        \Skeletor\Core\Activity\Service\Activity $activity) {
        parent::__construct($repo, $userSession, $logger, activity: $activity);
        $this->processor = $processor;
    }

    public function getEntityData($id)
    {
        $image = $this->repo->getById($id);

        return [
            'id' => $image->getId(),
            'alt' => $image->alt,
            'label' => $image->label,
            'author' => $image->author,
            'filename' => $image->filename,
            'type' => $image->type,
            'orientation' => $image->orientation,
            'createdAt' => $image->createdAt->format('m.d.Y'),
            'updatedAt' => $image->updatedAt->format('m.d.Y'),
        ];
    }

    public function prepareEntities($entities)
    {
        $items = [];
        foreach ($entities as $image) {
            $imgHtml = '';
            if ($image->filename !== '') {
                $imageUrl = "/images" . $image->filename;
                $imgHtml = '<img width="80px" src="'.$imageUrl.'" alt="image">';
            }

            $itemData = [
                'id' => $image->id,
                'filename' =>  [
                    'value' => $image->filename,
                    'editColumn' => true,
                ],
                'img' => $imgHtml,
                'mimeType' => $image->mimeType,
                'alt' => $image->alt,
                'type' => $image->type,
                'createdAt' => $image->createdAt->format('d.m.Y'),
                'updatedAt' => $image->updatedAt->format('d.m.Y'),
                'label' => $image->label,
                'author' => $image->author
            ];
            $items[] = [
                'columns' => $itemData,
                'id' => $image->id,
            ];
        }
        return $items;
    }

    public function compileTableColumns()
    {
        return [
            ['name' => 'filename', 'label' => 'Filename'],
            ['name' => 'img', 'label' => 'Image'],
            ['name' => 'mimeType', 'label' => 'Type'],
            ['name' => 'alt', 'label' => 'Alt'],
            ['name' => 'updatedAt', 'label' => 'Updated at'],
            ['name' => 'createdAt', 'label' => 'Created at'],
        ];
    }

    /**
     * @throws \Exception
     */
    public function update(array $data)
    {
        if (isset($data['image']) && $data['image']?->getSize() !== 0) {
            $imagePath = $this->processor->processUploadedFile($data['image']);
            $data['image'] = $imagePath;
        }
        if (isset($data['image']) && $data['image'] === '' && $data['oldImage'] !== '') {
            $data['image'] = $data['oldImage'];
        }
        unset($data['oldImage']);
        $oldModel = $this->repo->getById($data['id']);
        $model = $this->repo->update($data);

        return $model;
    }

    /**
     * @throws \Exception
     */
    public function create(array $data)
    {
        $imageData = $this->processor->processUploadedFile($data['image']);
        return $this->repo->create([
            'type' => 1,
            'mimeType' => 'jpg/jpeg',
            'filename' => $imageData['savePath'],
            'alt' => $data['alt'] ?? '',
            'label' => $data['label'] ?? '',
            'author' => $data['author'] ?? '',
            'orientation' => $this->getImageOrientation($imageData['image']),
        ]);
    }

    /**
     * @throws \ImagickException
     */
    private function getImageOrientation(\Imagick $image) :string
    {
        //return orientation
        if ($image->getImageWidth() > $image->getImageHeight()) {
            return 'landscape';
        }
        return 'portrait';
    }

    /**
     * @throws \Exception
     */
    public function createFromFile(UploadedFile $file)
    {
        return $this->repo->create([
            'type' => 1,
            'mimeType' => 'jpg/jpeg',
            'filename' => $this->processor->processUploadedFile($file)['savePath'],
            'alt' => $data['alt'] ?? '',
        ]);
    }

    public function createFromUrl($url)
    {
        return $this->repo->create([
            'type' => 1,
            'mimeType' => 'jpg/jpeg',
            'filename' => $this->processor->processRemoteFile($url)['savePath'],
            'alt' => $data['alt'] ?? '',
        ]);
    }

    public function createFromPath($path)
    {
        var_dump($path);
        die();
        return $this->repo->create([
            'type' => 1,
            'mimeType' => 'jpg/jpeg',
            'filename' => $this->processor->processImage($path),
            'alt' => $data['alt'] ?? '',
        ]);
    }

    public function regenerateImages()
    {
        /* @var \Skeletor\Image\Model\Image $image */
        foreach ($this->getEntities() as $image) {
            $path = DATA_PATH . $image->getFilename();
            $fname = explode('.', basename($path));
            var_dump();

            die();
            $imagick = new \Imagick(DATA_PATH . $image->getFilename());
        }
    }
}