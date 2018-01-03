<?php

require 'Config.php';

use Pem\Config;

### FUNCTIONS ###

function createTables($pdo) {
    $commands = ['CREATE TABLE IF NOT EXISTS sys_par (
                    name TEXT PRIMARY KEY,
                    value TEXT NOT NULL
                  )',
                 'CREATE TABLE IF NOT EXISTS posts (
                    id INTEGER NOT NULL,
                    uuid TEXT NOT NULL,
                    updated_at DATETIME NOT NULL,
                    article_id TEXT,
                    revision_id TEXT
                  )'];
    // execute the sql commands to create new tables
    foreach ($commands as $command) {
        $pdo->exec($command);
    }
}


### MAIN BODY ###

# Create the database
$db = new SQLite3(Config::SQLITE_PATH);

# Create the tables to be used
$pdo = new PDO('sqlite:' . Config::SQLITE_PATH);

createTables($pdo);

?>
