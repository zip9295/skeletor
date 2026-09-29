<?php

namespace Skeletor\Core\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Read-only entity mapping for the doctrine/migrations version table.
 *
 * It exists solely to stop schema-tool from dropping the table: the migration history is not part
 * of the ORM's mapped schema, so without this mapping an `orm:schema-tool:update --force` would
 * happily delete the record of which migrations have run. Same trick as before, repointed from
 * Phinx's `migrations_log` to `doctrine_migration_versions` — keep the column names in sync with
 * the `table_storage` block in data/migrations.php.
 */
#[ORM\Entity(readOnly: true)]
#[ORM\Table(name: 'doctrine_migration_versions')]
class MigrationLog
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, length: 191)]
    public string $version;

    #[ORM\Column(name: 'executed_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $executedAt = null;

    #[ORM\Column(name: 'execution_time', type: Types::INTEGER, nullable: true)]
    public ?int $executionTime = null;
}
