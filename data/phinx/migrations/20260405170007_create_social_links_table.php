<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSocialLinksTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
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

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS `social_links`");
    }
}
