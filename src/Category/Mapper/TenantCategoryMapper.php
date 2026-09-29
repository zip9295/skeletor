<?php
namespace Skeletor\Category\Mapper;

use Skeletor\Mapper\MysqlCrudMapper;
use Skeletor\Mapper\PDOWrite;

class TenantCategoryMapper extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'tenantCategoryMapping', 'tenantCategoryMappingId');
    }

    public function getCountForTenant(mixed $tenantId, int $categoryId)
    {
        $sql = sprintf("SELECT COUNT(*) as count FROM {$this->tableName} WHERE tenantId = {$tenantId}");
        if ($categoryId !== '') {
            $sql .= " AND mappedToId = {$categoryId}";
        }
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getMapped($tenantId, $limit, $offset)
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE tenantId = {$tenantId} AND mappedToId != 0 LIMIT {$limit} OFFSET {$offset};";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchResultsWithSearchExternal($search, $type, $tenantId, $mappingType, $limit, $offset, $searchType)
    {
        $typeSql = '';
        switch($type) {
            case 'mapped':
                $typeSql = ' mappedToId != 0';
                break;
            case 'unmapped':
                $typeSql = ' mappedToId = 0';
                break;
        }
        $sql = '';
        switch($searchType) {
            case 'source':
                $sql = " FROM {$this->tableName} WHERE {$typeSql} AND tenantId = {$tenantId} AND categoryId IN 
                (SELECT categoryId from category WHERE title LIKE '%{$search}%')
                LIMIT {$limit} OFFSET {$offset}";
                break;
            case 'local':
                $sql = "  FROM {$this->tableName} WHERE {$typeSql} AND tenantId = {$tenantId} AND mappedToId IN
                (SELECT categoryId from category WHERE title LIKE '%{$search}%')
                LIMIT {$limit} OFFSET {$offset}";
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