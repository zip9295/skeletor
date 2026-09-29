<?php
declare(strict_types = 1);
namespace Skeletor\Core\Mapper;

interface CrudMapperInterface
{
    function insert($modelData);

    function update($modelData);

    function fetchAll($params = array(), ?int $limit = null);

    function delete(int $modelId);
}
