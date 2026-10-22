<?php declare(strict_types=1);
require __DIR__ . "/../include/bootstrap.inc.php";

$setting = $cache?->load("setting"); // null with the cache off or before the first visit
if ($setting === null) {
	$setting = iterator_to_array($db->setting()->fetch()); // an array, a Row can't be serialized
	$cache?->save("setting", $setting);
}
$perPage = (int) $setting["per_page"]; // PDO MySQL returns strings before PHP 8.1
$orders = ["title" => "Title", "author.name" => "Author"];
$order = (isset($orders[$_GET["order"] ?? ""]) ? $_GET["order"] : "title"); // only the known columns, it goes to SQL
$page = $_GET["page"] ?? 1;
$search = $_GET["search"] ?? "";

$applications = $db->application();
if ($search != "") {
	$applications->where("title LIKE ? OR slogan LIKE ?", "%$search%", "%$search%");
}
$tag = null;
if (isset($_GET["tag"])) {
	$tag = $db->tag[$_GET["tag"]];
	$applications->where("application_tag:tag_id", $_GET["tag"]); // : joins the referencing table
}
$offset = ($page - 1) * $perPage;

page_header($setting["site_name"]);
?>
<form class="search" action=".">
<input type="search" name="search" value="<?=h($search)?>" placeholder="Title or slogan" aria-label="Search">
<?php if (isset($_GET["tag"])) { ?>
<input type="hidden" name="tag" value="<?=h($_GET["tag"])?>">
<?php } ?>
<button>Search</button>
<a class="button" href="application.php">New application</a>
</form>

<p><?=$count = $applications->count("*")?> applications<?php if (isset($_GET["tag"])) { ?> tagged <b><?=h($tag["name"] ?? "?")?></b> (<a href="<?=h(index_url(["tag" => null, "page" => null]))?>">all</a>)<?php } ?>.</p>

<table>
<thead><tr>
<?php foreach ($orders as $key => $label) { ?>
	<th><?=($key == $order ? $label : a(index_url(["order" => $key, "page" => null]), $label))?>
<?php } ?>
	<th>Tags
</thead>
<?php foreach ($applications->order($order, "application.id")->limit($perPage, $offset) as $application) { ?>
<tr>
	<td data-label="Title"><?=a("application.php?id=$application[id]", $application["title"])?><br><small><?=h($application["slogan"])?></small>
	<td data-label="Author"><?=a("author.php?id=$application[author_id]", $application->author["name"])?>
	<td data-label="Tags"><?php foreach ($application->application_tag() as $application_tag) { ?>
		<a class="tag" href="<?=h(index_url(["tag" => $application_tag["tag_id"], "page" => null]))?>"><?=h($application_tag->tag["name"])?></a>
	<?php } ?>
<?php } ?>
</table>

<?php if ($count > $perPage) { ?>
<nav class="pages">
<?php for ($i = 1; $i <= ceil($count / $perPage); $i++) { ?>
	<?=($i == $page ? "<b>$i</b>" : a(index_url(["page" => $i]), $i))?>
<?php } ?>
</nav>
<?php } ?>

<?php
page_footer($log, $cache);
