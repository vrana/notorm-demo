<?php declare(strict_types=1);

// Recreates the tables from sql/software.sql in the database from config.php: php setup.php
// Needs an account with CREATE and DROP, which the web account should not have.

if (PHP_SAPI != "cli") {
	exit("Run from the command line.\n");
}
require __DIR__ . "/vendor/autoload.php";
$config = require __DIR__ . "/config.php";
$connection = new PDO($config["dsn"], $argv[1] ?? $config["username"], $argv[2] ?? $config["password"], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach (preg_split('~;\r?\n~', file_get_contents(__DIR__ . "/sql/software.sql"), flags: PREG_SPLIT_NO_EMPTY) as $query) {
	if (trim(preg_replace('~^--.*~m', '', $query)) != "") {
		$connection->exec($query);
	}
}
echo "Created the tables from sql/software.sql.\n";
