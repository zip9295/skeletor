<?php

declare(strict_types=1);

namespace Skeletor\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405170002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create forgot password token table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE forgotPasswordToken (
                id INT AUTO_INCREMENT NOT NULL,
                token VARCHAR(128) NOT NULL,
                entityId VARCHAR(255) NOT NULL,
                entityType INT NOT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `forgotPasswordToken`");
    }
}
