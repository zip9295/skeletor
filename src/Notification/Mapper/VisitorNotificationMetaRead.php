<?php

namespace Skeletor\Notification\Mapper;

use Skeletor\Core\Mapper\MysqlReadMapper;
use Skeletor\Core\Mapper\PDORead;

class VisitorNotificationMetaRead extends MysqlReadMapper
{
    public function __construct(PDORead $pdo)
    {
        parent::__construct($pdo, 'visitor_notification_meta', 'id');
    }
}