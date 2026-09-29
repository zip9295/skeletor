<?php

declare(strict_types=1);

namespace Skeletor\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405165924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add page table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE page (
                id INT AUTO_INCREMENT NOT NULL,
                title VARCHAR(128) DEFAULT NULL,
                slug VARCHAR(128) DEFAULT NULL,
                blockData JSON DEFAULT NULL,
                status INT NOT NULL,
                seoTitle VARCHAR(128) NOT NULL,
                seoDescription VARCHAR(255) NOT NULL,
                featuredImageId INT DEFAULT NULL,
                seoImageId INT DEFAULT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE INDEX UNIQ_140AB620989D9B62 (slug),
                INDEX IDX_140AB62036FEEEF3 (featuredImageId),
                INDEX IDX_140AB620A3E9C5BE (seoImageId),
                PRIMARY KEY (id)
            );

        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `page`");
    }
}
