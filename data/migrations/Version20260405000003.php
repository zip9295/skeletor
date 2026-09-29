<?php

declare(strict_types=1);

namespace Skeletor\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create activity table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `activity` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `action` VARCHAR(16) NOT NULL COMMENT 'create, update, delete',
                `entityType` VARCHAR(64) NOT NULL COMMENT 'e.g. post, user, category',
                `entityId` INT UNSIGNED NOT NULL,
                `userId` INT UNSIGNED NULL,
                `oldData` TEXT NULL COMMENT 'JSON snapshot before change',
                `newData` TEXT NULL COMMENT 'JSON snapshot after change',
                `diff` TEXT NULL COMMENT 'JSON of changed fields only',
                `ipAddress` VARCHAR(45) NULL,
                `createdAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_entity` (`entityType`, `entityId`),
                INDEX `idx_user` (`userId`),
                INDEX `idx_action` (`action`),
                INDEX `idx_created` (`createdAt`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `activity`");
    }
}
