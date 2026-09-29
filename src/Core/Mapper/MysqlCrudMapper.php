<?php
declare(strict_types = 1);
namespace Skeletor\Core\Mapper;

/**
 * Class MysqlCrudMapper.
 * Represents default mysql (single table, no relations) crud operations.
 *
 * @package Skeletor\Mapper
 */
class MysqlCrudMapper implements CrudMapperInterface
{
    /**
     * @var \PDO
     */
    protected $driver;

    /**
     * @var
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $primaryKeyName;

    /**
     * BaseMapper constructor.
     * @param \PDO $pdo
     * @param $tableName
     */
    protected function __construct(\PDO $pdo, $tableName, $primaryKeyName = null)
    {
        $this->driver = $pdo;
        $this->tableName = $tableName;
        $this->primaryKeyName = $primaryKeyName;
        if (!$primaryKeyName) {
            $this->primaryKeyName = 'id';
        }
    }

    /**
     * Expects an associative array with (at least) required keys as fields names.
     * Does not care about data validation.
     *
     * @param $columnData
     * @return int
     * @throws \Exception
     */
    public function insert($columnData): int
    {
        foreach ($columnData as $key => $value) {
            if (is_object($value) && get_class($value) === \Skeletor\Core\Model\Model::class) {
                $columnData[$key . 'Id'] = $value->getId();
                unset($columnData[$key]);
            }
        }

        unset($columnData[$this->primaryKeyName]);
        if (isset($columnData['coordinates'])) {
            $coords = $columnData['coordinates'];
            $coords = sprintf("POINT(%s, %s)", $coords[0], $coords[1]);
            unset($columnData['coordinates']);
            $sql = sprintf(
                "INSERT INTO `%s` (`%s`, coordinate) VALUES (%s, $coords)", $this->tableName, implode('`, `', array_keys($columnData)),
                ':' . implode(', :', array_keys($columnData))
            );
        } else {
            $sql = sprintf(
                "INSERT INTO `%s` (`%s`) VALUES (%s)", $this->tableName, implode('`, `', array_keys($columnData)),
                ':' . implode(', :', array_keys($columnData))
            );
        }

        $statement = $this->driver->prepare($sql);
        foreach ($columnData as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
        if (!$statement->execute()) {
            $msg = sprintf('Sql: %s generated error: %s from: %s', $sql, print_r($statement->errorInfo(), true), get_class($this));
            throw new \Exception($msg);
        }

        return (int) $this->driver->lastInsertId();
    }

    public function updateField($field, $value, $entityId): bool
    {
        $set = $field . '=:' . $field;
        $sql = sprintf(
            "UPDATE `%s` SET %s WHERE `%s` = :%s", $this->tableName, $set, $this->primaryKeyName,
            $this->primaryKeyName
        );
        $statement = $this->driver->prepare($sql);
        $statement->bindValue(":$field", $value);
        $statement->bindValue(":$this->primaryKeyName", $entityId);

        if (!$statement->execute()) {
            throw new \Exception('Sql: ' . $sql . ' generated error:' . print_r($statement->errorInfo(), true));
        }
        return true;
    }

    /**
     * @param $columnData
     * @return int
     * @throws \Exception
     */
    public function update($columnData): int
    {
        $primaryKeyValue = $columnData[$this->primaryKeyName];
        unset($columnData[$this->primaryKeyName]);

        $updateSql = '';
        $firstLoop = false;
        foreach ($columnData as $key => $value){
            if ($firstLoop === false){
                $updateSql = sprintf(' `%s` = :%s, ', $key, $key);
                $firstLoop = true;
                continue;
            }
            if ($key === 'coordinates') {
                $coords = $columnData['coordinates'];
                $coords = sprintf("ST_GeomFromText('POINT(%s %s)')", $coords[0], $coords[1]);
                unset($columnData['coordinates']);
                $updateSql .= " `coordinate` = $coords,";
            } else {
                $updateSql .= sprintf(' `%s` = :%s,', $key, $key);
            }
        }
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :%s", $this->tableName, rtrim(trim($updateSql), ','), $this->primaryKeyName,
            $this->primaryKeyName
        );
        $statement = $this->driver->prepare($sql);
        foreach ($columnData as $key => $value) {
            $statement->bindValue(":$key", $value);
        }
        $statement->bindValue(":$this->primaryKeyName", $primaryKeyValue, \PDO::PARAM_INT);
        if (!$statement->execute()) {
            throw new \Exception('Sql: ' . $sql . ' generated error:' . print_r($statement->errorInfo(), true));
        }

        return (int) $primaryKeyValue;
    }

    /**
     * @param int $modelId
     * @return array
     */
    public function fetchById(int $modelId): array
    {
        $sql = "SELECT * FROM `{$this->tableName}` WHERE `{$this->primaryKeyName}` = $modelId";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $item = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$item) {
            throw new NotFoundException(sprintf('Entity %s not found: %d', get_class($this), $modelId));
        }

        return $item;
    }

    public function fetchByIds(array $modelIds): array
    {
        $ids = implode(',', $modelIds);
        $sql = "SELECT * FROM `{$this->tableName}` WHERE `{$this->primaryKeyName}` IN ($ids)";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param array $params
     * @param null $limit
     * @return array
     */
    public function fetchAll($params = array(), $limit = null, $order = null): array
    {
        $sql = "SELECT * FROM `{$this->tableName}` ";
        $i = 0;
        foreach ($params as $name => $value) {
            $sqlValue = "'{$value}'";
            if (is_numeric($value)) {
                $sqlValue = "{$value}";
            }
            if ($i === 0) {
                if ($value === null) {
                    $sql .= " WHERE `{$name}` IS NULL ";
                } else {
                    $sql .= " WHERE `{$name}` = $sqlValue ";
                }
            } else {
                if ($value === null) {
                    $sql .= " AND `{$name}` IS NULL ";
                } else {
                    $sql .= " AND `{$name}` = $sqlValue ";
                }
            }
            $i++;
        }
        if ($order) {
            $sql .= " ORDER BY {$order['orderBy']} {$order['dir']} ";
        }
        if ($limit) {
            if (is_int($limit)) {
                $sql .= " LIMIT " . $limit;
            } elseif (is_array($limit)) {
                $sql .= sprintf(" LIMIT %d,%d", $limit['offset'], $limit['limit']);
            } else {
                throw new \Exception('Unsupported limit type, use integer or array with offset/limit keys.');
            }
        }
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();

        return  $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function fetchTableData(
        $search, $filter = null, $searchableColumns = [], $offset = 0, $limit = 10, $order = false, $returnCount = false,
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
                    $sql .= " WHERE `{$name}` = {$sqlValue} ";
                } else {
                    $sql .= " AND `{$name}` = {$sqlValue} ";
                }
                $i++;
            }
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
            $sql .= ' AND (1=1 ';
        }
        if ($search) {
            $i = 0;
            foreach ($searchableColumns as $key => $column) {
                $sqlValue = "'$search'";
                if ($i === 0 && !$filterActive) {
                    $sql .= " WHERE `{$column}` LIKE {$sqlValue} ";
                } elseif($i === 0 && $filterActive) {
                    $sql .= " AND (`{$column}` LIKE {$sqlValue} ";
                    if(count($searchableColumns) === 1) {
                        $sql .= ' ) ';
                    }
                } else {
                    $closedParenthesis = '';
                    if ($filterActive && $key === array_key_last($searchableColumns)) {
                        $closedParenthesis = ')';
                    }
                    $sql .= " OR `{$column}` LIKE {$sqlValue} $closedParenthesis";
                }
                $i++;
            }
        }
        if ($filterActive && !$search) {
            $sql .= ' ) ';
        }
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

    /**
     * @param $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM `{$this->tableName}` WHERE `{$this->primaryKeyName}` = $id";

        return $this->driver->prepare($sql)->execute();
    }

    public function deleteBy($field, $value): bool
    {
        $sql = "DELETE FROM `{$this->tableName}` WHERE `{$field}` = $value";

        return $this->driver->prepare($sql)->execute();
    }

    /**
     * @return int
     */
    public function getTotalCount($uncountableFilter = null)
    {
        $sql = "SELECT COUNT(*) as count FROM `{$this->tableName}` ";
        if ($uncountableFilter) {
            $i = 0;
            foreach ($uncountableFilter as $name => $value) {
                $sqlValue = "'{$value}'";
                if (is_numeric($value)) {
                    $sqlValue = "{$value}";
                }
                if ($i === 0) {
                    $sql .= " WHERE `{$name}` = {$sqlValue} ";
                } else {
                    $sql .= " AND `{$name}` = {$sqlValue} ";
                }
                $i++;
            }
        }
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();

        return (int) $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
    }

    public function beginTransaction(): bool
    {
        return $this->driver->beginTransaction();
    }

    public function commitTransaction(): bool
    {
        return $this->driver->commit();
    }

    public function rollBackTransaction(): bool
    {
        return $this->driver->rollBack();
    }

    public function inTransaction(): bool
    {
        return $this->driver->inTransaction();
    }

    public function fetchDistinct($columnName): array
    {
        $sql = "SELECT DISTINCT $columnName FROM `{$this->tableName}`";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function getPrimaryKeyIdentifier()
    {
        return $this->primaryKeyName;
    }
}
