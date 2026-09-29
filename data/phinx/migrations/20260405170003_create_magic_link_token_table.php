<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateMagicLinkTokenTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE magic_link_token (
                id INT UNSIGNED AUTO_INCREMENT NOT NULL,
                token VARCHAR(255) NOT NULL,
                entityType VARCHAR(50) NOT NULL,
                entityId INT UNSIGNED NOT NULL,
                isValid TINYINT NOT NULL,
                createdAt DATETIME NOT NULL,
                expiresAt DATETIME NOT NULL,
                usedAt DATETIME DEFAULT NULL,
                ipAddress VARCHAR(45) DEFAULT NULL,
                UNIQUE INDEX UNIQ_E08FED565F37A13B (token),
                INDEX idx_token (token),
                INDEX idx_entity (entityType, entityId),
                INDEX idx_valid (isValid, expiresAt),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS `magic_link_token`");
    }
}
