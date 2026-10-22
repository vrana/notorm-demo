<?php declare(strict_types=1);

error_reporting(E_ALL);
require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/QueryLog.php";
require __DIR__ . "/CacheByDatabase.php";
require __DIR__ . "/functions.inc.php";
require __DIR__ . "/layout.inc.php";

// CSRF protection: browsers send Sec-Fetch-Site, clients without it can't be lured to post from another site
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_SERVER["HTTP_SEC_FETCH_SITE"] ?? "same-origin") != "same-origin") {
	http_response_code(403);
	header("Content-Type: text/plain; charset=utf-8");
	echo "Forms can be sent only from this site.\n";
	exit;
}

// the switches in the header post the database and the cache, remember them and go back to the page before it sees the POST
if (isset($_POST["cache"])) {
	$cookie = ["expires" => time() + 365 * 24 * 60 * 60, "path" => "/", "httponly" => true, "samesite" => "Lax"];
	setcookie("db", $_POST["db"], $cookie);
	if ($_POST["cache"] == "clear") { // the option after Session, the cache stays Session
		session_start();
		unset($_SESSION["NotORM"]); // everything NotORM\Cache\Session stored
	} else {
		setcookie("cache", $_POST["cache"], $cookie);
	}
	header("Location: $_SERVER[REQUEST_URI]", true, 303);
	exit;
}

/** @var array{dbs: non-empty-array<string, array{string, string, string}>, caches:non-empty-array<class-string<NotORM\Cache>|'', list<mixed>|Closure(PDO): list<mixed>>, source: string} $config */
$config = require __DIR__ . "/../config.php";
$caches = []; // short class name => [class, constructor arguments], "" is off
foreach ($config["caches"] as $class => $arguments) {
	$caches[substr((string) strrchr($class, "\\"), 1)] = [$class, $arguments]; // (string) - strrchr() returns false for ""
}
define("CACHES", array_keys($caches)); // read by cache_choice() and the switch in page_header()
define("DBS", array_keys($config["dbs"])); // read by db_choice() and the switch in page_header()
[$dsn, $username, $password] = $config["dbs"][db_choice()];
$connection = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_CASE => PDO::CASE_LOWER]); // CASE_LOWER - Oracle returns the column names in upper case
if ($connection->getAttribute(PDO::ATTR_DRIVER_NAME) == "sqlite") {
	$connection->exec("PRAGMA foreign_keys = ON"); // off by default, the demo shows the errors and ON DELETE CASCADE
}
[$class, $arguments] = $caches[cache_choice()];
if ($arguments instanceof Closure) {
	$arguments = $arguments($connection); // only for the chosen cache, e.g. a connection to Memcached
}
$cache = ($class == "" ? null : new CacheByDatabase(new $class(...$arguments), db_choice()));
if ($cache?->cache instanceof NotORM\Cache\Session) {
	session_start();
}

$structure = new NotORM\Structure\Discovery($connection, $cache, "%s_id"); // $application->author reads author_id
$software = new NotORM\NotORM($connection, $structure, $cache);

$log = new QueryLog($connection, $config["source"]);
$log->attach($software);
ob_start(); // page_footer() puts markers where the queries ran into the page
