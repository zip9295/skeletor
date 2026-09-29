<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateImageTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE image (
                id INT AUTO_INCREMENT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                alt VARCHAR(255) DEFAULT NULL,
                type INT NOT NULL,
                mimeType VARCHAR(255) NOT NULL,
                label VARCHAR(255) DEFAULT NULL,
                author VARCHAR(255) DEFAULT NULL,
                orientation VARCHAR(255) DEFAULT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS `image`");
    }
}
