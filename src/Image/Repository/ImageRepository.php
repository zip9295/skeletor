<?php
namespace Skeletor\Image\Repository;

use Skeletor\Core\TableView\Repository\TableViewRepository;
use Skeletor\Image\Model\Image as Model;

class ImageRepository extends TableViewRepository
{
    const ENTITY = \Skeletor\Image\Entity\Image::class;
    const FACTORY = \Skeletor\Image\Factory\ImageFactory::class;

    public function getSearchableColumns(): array
    {
        return ['a.filename', 'a.alt', 'a.label', 'a.author'];
    }
}
