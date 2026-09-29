<?php

declare(strict_types=1);

namespace Skeletor\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create reference table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `reference` (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(128) NOT NULL,
                comment VARCHAR(255) DEFAULT NULL,
                content TEXT NOT NULL,
                status INT NOT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `reference`");
    }
}
