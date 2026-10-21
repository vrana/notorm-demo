<?php declare(strict_types=1);

error_reporting(E_ALL);
require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/QueryLog.php";
require __DIR__ . "/functions.inc.php";
require __DIR__ . "/layout.inc.php";

// CSRF protection: browsers send Sec-Fetch-Site, clients without it can't be lured to post from another site
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_SERVER["HTTP_SEC_FETCH_SITE"] ?? "same-origin") != "same-origin") {
	http_response_code(403);
	header("Content-Type: text/plain; charset=utf-8");
	echo "Forms can be sent only from this site.\n";
	exit;
}

// the switch in the header posts the cache, remember it and go back to the page before it sees the POST
if (isset($_POST["cache"])) {
	if ($_POST["cache"] == "clear") { // the option after Session, the cache stays Session
		session_start();
		unset($_SESSION["NotORM"]); // everything NotORM\Cache\Session stored
	} else {
		setcookie("cache", $_POST["cache"], ["expires" => time() + 365 * 24 * 60 * 60, "path" => "/", "httponly" => true, "samesite" => "Lax"]);
	}
	header("Location: $_SERVER[REQUEST_URI]", true, 303);
	exit;
}

/** @var array{dsn: string, username: string, password: string, caches: non-empty-array<class-string<NotORM\Cache>|'', list<mixed>|Closure(PDO): list<mixed>>, source: string} $config */
$config = require __DIR__ . "/../config.php";
$caches = []; // short class name => [class, constructor arguments], "" is off
foreach ($config["caches"] as $class => $arguments) {
	$caches[substr((string) strrchr($class, "\\"), 1)] = [$class, $arguments]; // (string) - strrchr() returns false for ""
}
define("CACHES", array_keys($caches)); // read by cache_choice() and the switch in page_header()
$connection = new PDO($config["dsn"], $config["username"], $config["password"], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
[$class, $arguments] = $caches[cache_choice()];
if ($arguments instanceof Closure) {
	$arguments = $arguments($connection); // only for the chosen cache, e.g. a connection to Memcached
}
$cache = ($class == "" ? null : new $class(...$arguments));
if ($cache instanceof NotORM\Cache\Session) {
	session_start();
}

$structure = new NotORM\Structure\Discovery($connection, $cache, "%s_id"); // $application->author reads author_id
$software = new NotORM\NotORM($connection, $structure, $cache);

$log = new QueryLog($connection, $config["source"]);
$log->attach($software);
ob_start(); // page_footer() puts markers where the queries ran into the page
