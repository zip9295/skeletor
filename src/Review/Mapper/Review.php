<?php
namespace Skeletor\Review\Mapper;

use Skeletor\Core\Mapper\MysqlCrudMapper;
use Skeletor\Core\Mapper\PDOWrite;

class Review extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'review', 'id');
    }

    public function getAverageRating($entityId, $entityType): int
    {
        $sql = "SELECT AVG(rating) as avgRating FROM `{$this->tableName}` WHERE `entityId` = {$entityId} 
           AND `entityType` = {$entityType} AND status = " . \Skeletor\Review\Model\Review::STATUS_PUBLISHED ;
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$item) {
            return 0;
        }

        return (int) $item['avgRating'];
    }

    public function getRatingCount($entityId, $entityType): int
    {
        $sql = "SELECT COUNT(id) as count FROM {$this->tableName} WHERE `entityId` = ${entityId}
                AND `entityType` = {$entityType}
                AND `replyTo` IS NULL
                AND status = " . \Skeletor\Review\Model\Review::STATUS_PUBLISHED  . ";";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        if($result === false) {
            return 0;
        }
        return $result['count'];
    }

    public function getUserCommentBasedOnTimeout($visitorId, $entityId, $entityType, $timeout)
    {
        $sql = "SELECT id FROM {$this->tableName} WHERE `entityId` = {$entityId} AND `entityType` = {$entityType}
        AND visitorId = {$visitorId} AND createdAt >= (NOW() - INTERVAL {$timeout} SECOND) LIMIT 1";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchTableData(
        $search,
        $filter = null,
        $searchableColumns = [],
        $offset = 0,
        $limit = 10,
        $order = false,
        $returnCount = false,
        $uncountableFilter = null
    ) {
        $sql = "SELECT * FROM `{$this->tableName}` ";
        if($returnCount) {
            $sql = "SELECT COUNT(*) FROM `{$this->tableName}` ";
        }
        $i = 0;
        $filterActive = false;
        if ($filter) {
            foreach ($filter as $name => $value) {
                $filterActive = true;
                $sqlValue = "'{$value}'";
                if (is_numeric($value)) {
                    $sqlValue = "{$value}";
                }
                if ($i === 0) {
                    $sql .= " WHERE (`{$name}` = {$sqlValue} ";
                } else {
                    $sql .= " AND `{$name}` = {$sqlValue} ";
                }
                $i++;
            }
            $sql .= ') ';
        }
        if ($uncountableFilter) {
            $i = 0;
            $filterActive = true;
            foreach ($uncountableFilter as $name => $value) {
                $sqlValue = "'{$value}'";
                if (is_numeric($value)) {
                    $sqlValue = "{$value}";
                }
                if ($i === 0 && !$filter) {
                    $sql .= " WHERE `{$name}` = {$sqlValue} ";
                } else {
                    $sql .= " AND `{$name}` = {$sqlValue} ";
                }
                $i++;
            }
        }
        if ($filterActive && !$search) {
            $sql .= ' AND (1=1) ';
        }
        if ($search) {
            $i = 0;
            foreach ($searchableColumns as $key => $column) {
                $sqlValue = "'$search'";
                if ($i === 0 && !$filterActive) {
                    $sql .= " WHERE (`{$column}` LIKE {$sqlValue} ";
                } elseif($i === 0 && $filterActive) {
                    $sql .= " AND (`{$column}` LIKE {$sqlValue} ";
                } else {
                    $sql .= " OR `{$column}` LIKE {$sqlValue} ";
                }
                $i++;
            }
            $sql .= ') ';
        }

        if(!$search && !$filterActive) {
            $sql .= " WHERE replyTo IS NULL";
        }

        if($search || $filterActive) {
            $sql .= " AND replyTo is NULL";
        }
//        var_dump($sql);


        if(!$returnCount) {
            if ($order) {
                $sql .= " ORDER BY {$order['orderBy']} {$order['order']} ";
            }
            $sql .= sprintf(" LIMIT %d,%d", $offset, $limit);
        }

        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        if($returnCount) {
            return  $stmt->fetch(\PDO::FETCH_COLUMN);
        }
        return  $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getTotalCount($uncountableFilter = null)
    {
        $sql = "SELECT COUNT(*) as count FROM `{$this->tableName}` WHERE replyTo IS NULL";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();

        return (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    public function deleteByReplyTo($id)
    {
        $sql = "DELETE FROM {$this->tableName} WHERE replyTo = {$id}";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
    }
}