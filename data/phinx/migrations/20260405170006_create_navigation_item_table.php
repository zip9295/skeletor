<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNavigationItemTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE navigationItem (
                id INT AUTO_INCREMENT NOT NULL,
                label VARCHAR(128) NOT NULL,
                url LONGTEXT NOT NULL,
                icon LONGTEXT DEFAULT NULL,
                position INT NOT NULL,
                openInNewTab SMALLINT DEFAULT 0 NOT NULL,
                parent INT DEFAULT NULL,
                navigation_id INT DEFAULT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX IDX_AAAC11313D8E604F (parent),
                INDEX IDX_AAAC113139F79D6D (navigation_id),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS `navigationItem`");
    }
}
