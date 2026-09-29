<?php

declare(strict_types=1);

namespace Skeletor\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405170007 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create social links table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE social_links (
                id INT AUTO_INCREMENT NOT NULL,
                platform VARCHAR(128) NOT NULL,
                url LONGTEXT NOT NULL,
                position INT NOT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX UNIQ_9B12158A3952D0CB (platform),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `social_links`");
    }
}
