<?php
namespace Skeletor\File\Repository;

use Skeletor\Core\TableView\Repository\TableViewRepository;

class FileRepository extends TableViewRepository
{
    const ENTITY = \Skeletor\File\Entity\File::class;
    const FACTORY = \Skeletor\File\Factory\FileFactory::class;

    public function getSearchableColumns(): array
    {
        return ['a.filename', 'a.mimeType'];
    }

}
