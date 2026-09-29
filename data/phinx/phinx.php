<?php

return [
    "paths"        => [
        "migrations" => "%%PHINX_CONFIG_DIR%%/migrations/"
    ],
    "environments" => [
        "default_migration_table" => "migrations_log",
        "default_database"        => "default",

        // all DB connections
        "default"                 => [
            "adapter" => 'mysql',
            "host"    => 'localhost',
            "name"    => 'skeleton',
            "user"    => 'root',  // set username
            "pass"    => 'rootpass', // and pass
            "port"    => '3306'
        ]
    ]
];
