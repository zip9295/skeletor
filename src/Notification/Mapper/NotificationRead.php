<?php

namespace Skeletor\Notification\Mapper;

use Skeletor\Core\Mapper\MysqlReadMapper;
use Skeletor\Core\Mapper\PDORead;

class NotificationRead extends MysqlReadMapper
{
    public function __construct(PDORead $pdo)
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
        return true;
    }
}