<?php
namespace Skeletor\Core\Cache\Service;

use Symfony\Component\Filesystem\Filesystem;

class FullPageCache implements FullPageCacheInterface
{
    const FULL_PAGE_CACHE_PATH = DATA_PATH . '/cache/fpc';

    private $fileSystem;

    public function __construct(Filesystem $fileSystem)
    {
        $this->fileSystem = $fileSystem;
        $this->createRequiredFolders();
    }

    public function resetCache()
    {
        foreach (new \DirectoryIterator(static::FULL_PAGE_CACHE_PATH . '/') as $directory) {
            if (!$directory->isDot()) {
                if ($directory->isDir()) {
                    $this->fileSystem->remove($directory->getRealPath());
                }
            }
        }
    }

    public function deleteBySlug($item)
    {
        $targetPath = static::FULL_PAGE_CACHE_PATH . "/{$item}";
        try {
            $this->fileSystem->remove($targetPath);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
            die();
        }
        return true;
    }

    public function getCachedItems()
    {
        $dIterator = new \DirectoryIterator(static::FULL_PAGE_CACHE_PATH . '/');
        $data = [];
        foreach ($dIterator as $directory) {
            if (!$directory->isDot()) {
                if ($directory->isDir()) {
                    $fIterator = new \DirectoryIterator($directory->getRealPath());
                    foreach ($fIterator as $file) {
                        if (!$file->isDot()) {
                            $data[$directory->getFilename()]['size'] = $file->getSize();
                            $data[$directory->getFilename()]['path'] = $file->getRealPath();
                            $data[$directory->getFilename()]['mtime'] = $file->getMTime();
                        }
                    }
                }
            }
        }
        return $data;
    }

    private function createRequiredFolders()
    {
        try {
            $this->fileSystem->mkdir([
                DATA_PATH . '/cache',
                DATA_PATH . '/cache/fpc',
//                DATA_PATH . '/cache/fpc', // @TODO get this from config
            ]);
        } catch (\Exception $e) {
            var_dump($e->getMessage());
        }
    }
}