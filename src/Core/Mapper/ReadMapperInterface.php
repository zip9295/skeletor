<?php
declare(strict_types = 1);
namespace Skeletor\Core\Mapper;

interface ReadMapperInterface
{
    function fetchAll($params = array(), ?int $limit = null);

    function fetchById(int $modelId);
}
