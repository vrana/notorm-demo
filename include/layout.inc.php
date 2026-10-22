<?php declare(strict_types=1);

/** Print the page start
* @param string $notice HTML from demo_write()
*/
function page_header(string $title, string $notice = ""): void {
	$page = basename(get_included_files()[0]);
	$page = ($page == "index.php" ? "." : $page);
	$menu = [
		"." => "Applications",
		"authors.php" => "Authors",
		"tags.php" => "Tags",
		"categories.php" => "Categories",
	];
	?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?=h($title)?> - NotORM demo</title>
<link rel="stylesheet" href="static/demo.css?v=<?=filemtime(__DIR__ . "/../www/static/demo.css")?>">
<link rel="icon" href="https://www.notorm.com/favicon.ico">
</head>
<body>
<header>
<a class="brand" href=".">NotORM <span>demo</span></a>
<nav>
<?php foreach ($menu as $href => $label) { ?>
<a href="<?=$href?>"<?=($href == $page ? " aria-current='page'" : "")?>><?=$label?></a>
<?php } ?>
</nav>
<form class="switch" method="post">
<label>Database <select name="db">
<?php foreach (DATABASES as $value) { ?>
<option<?=($value == db_choice() ? " selected" : "")?>><?=h($value)?></option>
<?php } ?>
</select></label>
<label>Cache <select name="cache">
<?php foreach (CACHES as $value) { ?>
<option value="<?=h($value ?: "off")?>"<?=($value == cache_choice() ? " selected" : "")?>><?=h($value ?: "off")?></option>
<?php if ($value == cache_choice() && $value == "Session") { ?>
<option value="clear">CLEAR!</option>
<?php } ?>
<?php } ?>
</select></label>
<noscript><button>Set</button></noscript>
</form>
</header>
<main>
<?=($_POST ? "" : $notice)?>
<h1><?=h($title)?></h1>
<?=($_POST ? $notice : "")?>
<?php
}

/** Print the page with the places of the queries marked, the queries and the page end */
function page_footer(QueryLog $log, ?CacheByDatabase $cache): void {
	$html = (string) ob_get_clean();
	$notice = strpos($html, "<p class='notice");
	echo $log->mark($html, (int) strpos($html, "<h1>"), ($notice === false ? null : $notice)); // the queries run before the page printed anything go before the title or the notice
	echo "</main>\n";
	$log->render();
	?>
<footer>
<?php if ($cache) { ?>
<p>NotORM remembers which columns each page used, in <code><?=h(get_class($cache->cache))?></code>.
The first visit of a page selects <code class="jush-sql">*</code>, the next ones only these columns.</p>
<?php } else { ?>
<p>The cache is off, so NotORM doesn't know which columns the page uses and selects <code class="jush-sql">*</code>.</p>
<?php } ?>
<p><a href="https://www.notorm.com/" target="_blank">NotORM</a>
· <a href="https://github.com/vrana/notorm" target="_blank">NotORM on GitHub</a>
· <a href="https://github.com/vrana/notorm-demo" target="_blank">Source of this demo</a></p>
</footer>
<script src="static/jush/jush.js?v=<?=filemtime(__DIR__ . "/../www/static/jush/jush.js")?>"></script>
<script src="static/demo.js?v=<?=filemtime(__DIR__ . "/../www/static/demo.js")?>"></script>
</body>
</html>
<?php
}

/** Print a 404 page and stop */
function not_found(QueryLog $log, ?CacheByDatabase $cache): void {
	http_response_code(404);
	page_header("Not found");
	echo "<p>There is no such row. <a href='.'>Back to the applications</a>.</p>\n";
	page_footer($log, $cache);
	exit;
}
