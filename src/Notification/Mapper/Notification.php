<?php

namespace Skeletor\Notification\Mapper;

use Skeletor\Core\Mapper\MysqlCrudMapper;
use Skeletor\Core\Mapper\PDOWrite;

class Notification extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'notification', 'id');
    }

    public function doesNotificationExistForEntity($entityId, $entityType, $notificationType)
    {
        $sql = "SELECT COUNT(id) as count FROM {$this->tableName} WHERE entityId = {$entityId} 
                                         AND entityType = {$entityType}
                                         AND notificationType = {$notificationType}";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        if($result === false) {
            return false;
        }
        return $result['count'] > 0;
    }

    public function deleteByeEntityForType($entityId, $entityType, $notificationType)
    {
        $sql = "DELETE from {$this->tableName} WHERE entityId = {$entityId} AND entityType = {$entityType}
                AND notificationType = {$notificationType}";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
    }
}