<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNavigationTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
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

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS `navigation`");
    }
}
