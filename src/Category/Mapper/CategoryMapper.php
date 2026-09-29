<?php
namespace Skeletor\Category\Mapper;

use Skeletor\Mapper\MysqlCrudMapper;
use Skeletor\Mapper\PDOWrite;

class CategoryMapper extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'categoryMapping', 'categoryMappingId');
    }

    /**
     * @throws \Exception
     */
    public function updateMargins($id, $data): void
    {
        $sql = "UPDATE {$this->tableName} SET rules = :rules, limiter = :limiter, margin = :margin WHERE categoryMappingId = {$id}";
        $statement = $this->driver->prepare($sql);
        $statement->bindParam(':rules', $data['rules']);
        $statement->bindParam(':limiter', $data['prices']);
        $statement->bindParam(':margin', $data['margins']);
        try {
            if(!$statement->execute()) {
                $msg = sprintf('Sql: %s generated error: %s from: %s', $sql, print_r($statement->errorInfo(), true), get_class($this));
                var_dump($msg);
//                throw new \Exception($msg);
            }
        } catch (\Exception $e) {
            $msg = sprintf('Sql: %s generated error: %s from: %s', $sql, print_r($statement->errorInfo(), true), get_class($this));
            var_dump($msg);
            var_dump($data);
            die();
//            throw new \Exception($msg);
        }

    }

    public function getMappingsByMarginGroupId($marginGroupId)
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE marginGroupId = {$marginGroupId}";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSourceIds(array $categoryIds, int $tenantId)
    {
        $categoryIds = implode(',', $categoryIds);
        $sql = "SELECT categoryId FROM tenantCategoryMapping WHERE mappedToId IN ({$categoryIds}) AND tenantId = {$tenantId};";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $ids = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $ids[] = $row['categoryId'];
        }

        return  $ids;
    }

    public function fetchResultsWithSearchSource($search, $type, $supplierId, $mappingType, $limit, $offset, $searchType)
    {
        $typeSql = '';
        switch($type) {
            case 'mapped':
                $typeSql = ' categoryId != 0 AND categoryId != -1';
                break;
            case 'unmapped':
                $typeSql = ' categoryId = 0';
                break;
            case 'ignored':
                $typeSql = ' categoryId = -1';
                break;
        }
        $sql = '';
        switch($searchType) {
            case 'source':
                $sql = " FROM {$this->tableName} WHERE supplierId = {$supplierId} AND {$typeSql} AND 
                          (source1 LIKE '%$search%' || source2 LIKE '%$search%' || source3 LIKE '%$search%')
                          ORDER BY count DESC LIMIT {$limit} OFFSET {$offset}";
                break;
            case 'local':
                $sql = " FROM {$this->tableName} WHERE supplierId = {$supplierId} AND {$typeSql} AND 
                          (categoryId IN (SELECT categoryId
                            from category where title LIKE '%$search%'))
                          ORDER BY count DESC LIMIT {$limit} OFFSET {$offset}";
                break;
        }
        $resultSql = "SELECT *" . $sql;
        $stmt = $this->driver->prepare($resultSql);
        $stmt->execute();
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $countSql = "SELECT COUNT(*)" . $sql;
        $stmt = $this->driver->prepare($countSql);
        $stmt->execute();
        $count = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        return [
            'results' => $results,
            'count' => $count
        ];

    }
}