<?php
namespace Skeletor\Category\Mapper;

use Skeletor\Mapper\MysqlCrudMapper;
use Skeletor\Mapper\PDOWrite;

class Category extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'category', 'categoryId');
    }

    public function fetchForApi($tenantId)
    {
        $sql = "SELECT categoryId FROM category WHERE `level` = 1 AND `tenantId` = {$tenantId}";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $ids = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if ($row['categoryId'] == 1) {
                continue;
            }
            $ids[] = $row['categoryId'];
        }
        $ids = implode(',', $ids);

        $sql = sprintf("SELECT * FROM category WHERE `parent` IN (%s) OR parent IN (
SELECT categoryId FROM category WHERE parent IN (%s)) OR categoryId IN (%s) ORDER BY level", $ids, $ids, $ids);
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();

        return  $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function updateProductCount($catId)
    {
        $sql = "SELECT COUNT(*) as count FROM product WHERE category = {$catId}";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $count = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
        $this->updateField('count', $count, $catId);
    }

}