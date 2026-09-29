<?php

namespace Skeletor\Attribute\Mapper;

use Skeletor\Core\Mapper\MysqlCrudMapper;
use Skeletor\Core\Mapper\PDOWrite;

class AttributeValue extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'attributeValues');
    }
}