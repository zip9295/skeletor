<?php

namespace Skeletor\Notification\Mapper;

use Skeletor\Core\Mapper\MysqlCrudMapper;
use Skeletor\Core\Mapper\PDOWrite;

class VisitorNotificationMeta extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'visitor_notification_meta', 'id');
    }

    public function getNonDismissedNotificationCountByVisitorId(int $visitorId): int
    {
        $sql = "SELECT COUNT(id) as count FROM {$this->tableName} WHERE dismissed = 0 AND visitorId = {$visitorId}";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        if($result === false) {
            return 0;
        }
        return $result['count'];
    }

    public function getLastNotificationTimestampForVisitor($visitorId)
    {
        $sql = "SELECT `createdAt` as timestamp FROM {$this->tableName} 
                 WHERE dismissed = 0 
                 AND visitorId = {$visitorId} ORDER BY createdAt DESC LIMIT 1";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();

        if(!isset($result['timestamp'])) {
            $sql = "SELECT CURRENT_TIMESTAMP() as timestamp";
            $stmt = $this->driver->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
        }
        return $result['timestamp'];
    }

    public function getNewNotificationCountAndTimestamp($visitorId, $timestamp)
    {
        $sql = "SELECT `createdAt` as timestamp FROM {$this->tableName} 
                WHERE dismissed = 0 AND visitorId = {$visitorId} AND createdAt > '{$timestamp}' ORDER BY createdAt DESC";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll();
        $data['count'] = count($result);
        if(count($result) > 0) {
           $data['timestamp'] = $result[0]['timestamp'];
        }
        return $data;
    }

    public function getNewNotificationsForVisitor($visitorId, $timestamp)
    {
        $sql = "SELECT notificationId, createdAt FROM {$this->tableName} 
                WHERE dismissed = 0 AND visitorId = {$visitorId} AND createdAt > '{$timestamp}' ORDER BY createdAt DESC";
        $stmt = $this->driver->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}