<?php
declare(strict_types = 1);
namespace Skeletor\Core\Mapper;

/**
 * Class MysqlCrudMapper.
 * Represents default mysql (single table, no relations) crud operations.
 *
 * @package Skeletor\Mapper
 */
class MysqlReadMapper implements ReadMapperInterface
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
            throw new NotFoundException('Entity not found: ' . $modelId);
        }

        return $item;
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
            if ($i === 0) {
                $sql .= " WHERE `{$name}` = '{$value}' ";
            } else {
                $sql .= " AND `{$name}` = '{$value}' ";
            }
            $i++;
        }
        if ($order) {
            $sql .= " ORDER BY {$order['orderBy']} {$order['dir']} ";
        }
        if (isset($limit)) {
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
}
