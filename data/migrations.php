<?php

declare(strict_types=1);

return [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
        'version_column_name' => 'version',
        'version_column_length' => 191,
        'executed_at_column_name' => 'executed_at',
        'execution_time_column_name' => 'execution_time',
    ],

    'migrations_paths' => [
        'Skeletor\Migrations' => DATA_PATH . '/migrations',
    ],

    // MySQL commits DDL implicitly, so a failed CREATE/ALTER halfway through a batch cannot be
    // rolled back no matter what we ask for here. Wrapping the batch in a transaction would only
    // give a false sense of safety: leaving this false means a failure stops at the migration that
    // broke, with everything before it recorded as executed, which is the honest and recoverable
    // state. Write migrations so each one is independently re-runnable.
    'all_or_nothing' => false,

    'check_database_platform' => true,
];
