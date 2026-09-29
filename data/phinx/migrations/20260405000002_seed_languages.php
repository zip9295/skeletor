<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class SeedLanguages extends AbstractMigration
{
    public function up(): void
    {
        $this->execute("
            CREATE TABLE language (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) NOT NULL,
                code VARCHAR(255) NOT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            );
        ");
        $this->execute("
            CREATE TABLE translation (
                id INT AUTO_INCREMENT NOT NULL,
                groupName VARCHAR(255) DEFAULT NULL,
                originalString VARCHAR(255) NOT NULL,
                translatedString VARCHAR(255) DEFAULT NULL,
                languageId INT DEFAULT NULL,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
                updatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX IDX_B469456F940D8C7E (languageId),
                UNIQUE INDEX translation_UNIQUE (originalString, languageId),
                PRIMARY KEY (id)
            );
        ");

        $this->execute("
            INSERT INTO `language` (`name`, `code`) VALUES
            ('english', 'en-us'),
            ('serbian', 'sr-sr')
        ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS `translation`");
        $this->execute("DROP TABLE IF EXISTS `language`");
    }
}

