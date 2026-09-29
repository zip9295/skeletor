<?php
namespace Skeletor\Category\Mapper;

use Skeletor\Mapper\MysqlCrudMapper;
use Skeletor\Mapper\PDOWrite;

class CategoryTenantSettings  extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'categoryTenantSettings', 'id');
    }
}