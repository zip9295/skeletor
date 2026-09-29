<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SeedTestUser extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE user (
        id INT AUTO_INCREMENT NOT NULL,
    firstName VARCHAR(128) DEFAULT NULL,
    lastName VARCHAR(128) DEFAULT NULL,
    email VARCHAR(128) NOT NULL,
    password VARCHAR(128) NOT NULL,
    role SMALLINT NOT NULL,
    isActive INT NOT NULL,
    displayName VARCHAR(128) NOT NULL,
    ipv4 INT UNSIGNED DEFAULT NULL,
    lastLogin DATETIME DEFAULT NULL,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
    PRIMARY KEY (id))
        ");

        $password = password_hash('testtest', PASSWORD_BCRYPT);

        $this->execute("
            INSERT INTO `user` (`firstName`, `lastName`, `email`, `password`, `role`, `isActive`, `displayName`)
            VALUES ('Test', 'User', 'test@example.com', '{$password}', 1, 1, 'Test User')
        ");

    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS `user`");
    }
}
