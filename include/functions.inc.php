<?php declare(strict_types=1);

/** Escape for HTML */
function h(mixed $string): string {
	return htmlspecialchars((string) $string, ENT_QUOTES | ENT_SUBSTITUTE);
}

/** Link with escaped text, external links open in a new window */
function a(string $href, mixed $text): string {
	return "<a href='" . h($href) . "'" . (preg_match('~^https?://~i', $href) ? " target='_blank'" : "") . ">" . h($text) . "</a>";
}

/** Count with a noun, e.g. "0 authors", "1 author" */
function plural(int $count, string $noun): string {
	return "$count $noun" . ($count == 1 ? "" : "s");
}

/** Cache chosen by the visitor in the header, one of CACHES: the short class names from config.php, "" is off */
function cache_choice(): string {
	$choice = $_COOKIE["cache"] ?? CACHES[0]; // no cookie before the first switch
	$choice = ($choice == "off" ? "" : $choice); // a cookie can't be empty, the switch sends off
	return (in_array($choice, CACHES, true) ? $choice : CACHES[0]); // e.g. a cache which is no longer allowed
}

/** JUSH language of the SQL dialect, build-jush.php builds these */
function sql_language(PDO $pdo): string {
	return match ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME)) {
		"sqlite" => "sqlite",
		"pgsql" => "pgsql",
		"sqlsrv", "dblib", "mssql" => "mssql",
		"oci" => "oracle",
		default => "sql", // MySQL and MariaDB
	};
}

/** Database chosen by the visitor in the header, one of DATABASES: the names from config.php */
function db_choice(): string {
	$choice = $_COOKIE["db"] ?? DATABASES[0]; // no cookie before the first switch
	return (in_array($choice, DATABASES, true) ? $choice : DATABASES[0]); // e.g. a database which is no longer configured
}

/** Milliseconds with two decimals */
function format_time(float $milliseconds): string {
	return number_format($milliseconds, 2) . " ms";
}

/** Current URL of the applications list with changed parameters
* @param array<string, scalar|null> $parameters null removes the parameter
*/
function index_url(array $parameters): string {
	$query = http_build_query(array_filter(array_merge($_GET, $parameters), fn($value) => $value !== null && $value !== ""));
	return ($query != "" ? "./?$query" : ".");
}

/** <option>s from rows
* @param iterable<NotORM\Row> $rows
*/
function options(iterable $rows, string $column, mixed $selected): string {
	$return = "";
	foreach ($rows as $row) {
		$return .= "<option value='" . h($row) . "'" . ((string) $row === (string) $selected ? " selected" : "") . ">" . h($row[$column]) . "</option>";
	}
	return $return;
}

/** Run the writes in a transaction which is always rolled back
* @param callable(): string $write returns what was done, e.g. "Updated 1 row"
* @return string HTML of the notice
*/
function demo_write(NotORM\NotORM $db, QueryLog $log, callable $write): string {
	$from = $log->count();
	$db->transaction = "BEGIN";
	try {
		$return = "<p class='notice'>" . h($write()) . ". <b>This demo is read-only</b>, the change was rolled back.</p>\n";
	} catch (PDOException $exception) {
		$return = "<p class='notice error'>Error: " . h(rtrim($exception->getMessage(), ".")) . ".</p>\n";
	} finally {
		$db->transaction = "ROLLBACK";
	}
	$log->notice($from); // the writes go before the notice
	return $return;
}
