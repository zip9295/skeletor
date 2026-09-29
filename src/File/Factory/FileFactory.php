<?php
namespace Skeletor\File\Factory;

use Skeletor\Core\Factory\AbstractFactory;
use Skeletor\File\Entity\File;

class FileFactory extends AbstractFactory
{
    public static function compileEntityForUpdate($data, $em)
    {
        $entity = $em->getRepository(File::class)->find($data['id']);
        $entity->filename = $data['filename'];
        $entity->mimeType = $data['mimeType'];
        return $entity;
    }
}