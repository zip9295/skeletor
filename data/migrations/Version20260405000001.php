<?php

declare(strict_types=1);

namespace Skeletor\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260405000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'seed test user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("
            CREATE TABLE `user` (
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
                PRIMARY KEY (id)
            )
        ");

        // Bound rather than interpolated. The Phinx original computed the hash into a local
        // variable and dropped it into the SQL string; as a bound parameter the credential stays
        // out of any --dry-run or dump-schema output.
        $this->addSql(
            "INSERT INTO `user` (`firstName`, `lastName`, `email`, `password`, `role`, `isActive`, `displayName`)
             VALUES ('Test', 'User', 'test@example.com', ?, 1, 1, 'Test User')",
            [password_hash('testtest', PASSWORD_BCRYPT)]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DROP TABLE IF EXISTS `user`");
    }
}
