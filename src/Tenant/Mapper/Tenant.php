<?php
namespace Skeletor\Tenant\Mapper;

use Skeletor\Core\Mapper\MysqlCrudMapper;
use Skeletor\Core\Mapper\PDOWrite;

class Tenant extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'tenant');
    }
}