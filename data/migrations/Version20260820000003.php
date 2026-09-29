<?php

declare(strict_types=1);

namespace Skeletor\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260820000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'create author table';
    }

    public function up(Schema $schema): void
    {
        // Indexes but no foreign keys on avatarId / seoImageId, matching every other table in this
        // migration set (page, post and image are wired the same way).
        $this->addSql("
            CREATE TABLE `author` (
                id INT AUTO_INCREMENT NOT NULL,
                firstName VARCHAR(128) NOT NULL,
                lastName VARCHAR(128) NOT NULL,
                displayName VARCHAR(128) DEFAULT NULL,
                isActive TINYINT(1) NOT NULL DEFAULT 1,
                description TEXT DEFAULT NULL,
                shortDescription TEXT DEFAULT NULL,
                avatarId INT DEFAULT NULL,
                seoTitle VARCHAR(128) NOT NULL,
                seoDescription VARCHAR(255) NOT NULL,
                seoImageId INT DEFAULT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX IDX_AUTHOR_AVATAR (avatarId),
                INDEX IDX_AUTHOR_SEO_IMAGE (seoImageId),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `author`");
    }
}
