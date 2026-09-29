<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateForgotPasswordTokenTable extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE forgotPasswordToken (
                id INT AUTO_INCREMENT NOT NULL,
                token VARCHAR(128) NOT NULL,
                entityId VARCHAR(255) NOT NULL,
                entityType INT NOT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS `forgotPasswordToken`");
    }
}
