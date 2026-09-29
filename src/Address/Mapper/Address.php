<?php
declare(strict_types = 1);
namespace Skeletor\Address\Mapper;

use Skeletor\Core\Mapper\MysqlCrudMapper;
use Skeletor\Core\Mapper\PDOWrite;

class Address extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'address', 'addressId');
    }
}
