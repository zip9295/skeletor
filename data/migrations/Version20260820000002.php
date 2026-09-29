<?php

declare(strict_types=1);

namespace Skeletor\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create lead table';
    }

    public function up(Schema $schema): void
    {
        // `lead` is a reserved word in MySQL 8, hence the backticks here and in the entity's
        // #[ORM\Table(name: '`lead`')] mapping.
        $this->addSql("
            CREATE TABLE `lead` (
                id INT AUTO_INCREMENT NOT NULL,
                firstName VARCHAR(128) DEFAULT NULL,
                lastName VARCHAR(128) DEFAULT NULL,
                email VARCHAR(128) NOT NULL,
                phoneNumber VARCHAR(64) DEFAULT NULL,
                status INT DEFAULT NULL,
                source VARCHAR(128) DEFAULT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX UNIQ_LEAD_EMAIL (email),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `lead`");
    }
}
