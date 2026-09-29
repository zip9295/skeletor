<?php

declare(strict_types=1);

namespace Skeletor\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405170005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create navigation table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE navigation (
                id INT AUTO_INCREMENT NOT NULL,
                label VARCHAR(128) NOT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX UNIQ_493AC53FEA750E8 (label),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `navigation`");
    }
}
