<?php
namespace Skeletor\Category\Mapper;

use Skeletor\Mapper\MysqlCrudMapper;
use Skeletor\Mapper\PDOWrite;

class UnMappedCategory extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'unMappedCategory', 'unMappedCategoryId');
    }

    public function getCountPerSupplier()
    {
        $sql = sprintf("SELECT COUNT(*) as count, supplierId FROM unMappedCategory GROUP BY supplierId");
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $items = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $items[$row['supplierId']] = (int) $row['count'];
        }

        return $items;
    }
}