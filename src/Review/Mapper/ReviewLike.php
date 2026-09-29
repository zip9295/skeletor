<?php
namespace Skeletor\Review\Mapper;

use Skeletor\Core\Mapper\MysqlCrudMapper;
use Skeletor\Core\Mapper\PDOWrite;

class ReviewLike extends MysqlCrudMapper
{
    public function __construct(PDOWrite $pdo)
    {
        parent::__construct($pdo, 'reviewLike', 'id');
    }

    public function unlike($reviewId, $visitorId): bool
    {
        $sql = "DELETE FROM `{$this->tableName}` WHERE `visitorId` = $visitorId AND `reviewId` = $reviewId";

        return $this->driver->prepare($sql)->execute();
    }
}