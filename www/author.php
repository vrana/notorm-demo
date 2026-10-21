<?php declare(strict_types=1);
require __DIR__ . "/../include/bootstrap.inc.php";

$id = $_GET["id"] ?? ""; // no id for a new row
$notice = "";
if ($_POST) {
	$data = [
		"name" => $_POST["name"],
		"web" => $_POST["web"],
	];
	$notice = demo_write($software, $log, function () use ($software, $id, $data) {
		if (isset($_POST["delete"])) {
			$deleted = $software->author("id", $id)->delete(); // fails if the author has applications
			return "Deleted " . plural($deleted, "author");
		}
		if (!$id) {
			$author = $software->author()->insert($data);
			return "Inserted author $author[id]";
		}
		$updated = $software->author("id", $id)->update($data);
		return "Updated " . plural($updated, "author");
	});
}

$author = null;
if ($id) {
	$author = $software->author[$id];
	if (!$author) {
		not_found($log, $cache);
	}
}
$form = ($_POST ? $data : $author); // after a POST, show what was sent

page_header($author["name"] ?? "New author", $notice);
if ($author) {
	?>
<dl>
	<dt>Web<dd><?=a($author["web"], $author["web"])?>
</dl>

<h2>Applications</h2>
<ul>
<?php foreach ($author->application()->via("author_id")->order("title") as $application) { ?>
	<li><?=a("application.php?id=$application[id]", $application["title"])?>
		<?php if ($application["maintainer_id"] != $author["id"] && $application->maintainer) { ?><small>maintained by <?=a("author.php?id=$application[maintainer_id]", $application->maintainer["name"])?></small><?php } ?>
<?php } ?>
</ul>

<h2>Maintained applications</h2>
<ul>
<?php foreach ($author->application()->via("maintainer_id")->order("title") as $application) { ?>
	<li><?=a("application.php?id=$application[id]", $application["title"])?>
<?php } ?>
</ul>

<h2>Edit</h2>
<?php
}
?>
<form class="edit" method="post">
	<label for="name">Name</label>
	<input id="name" name="name" value="<?=h($form["name"] ?? "")?>" maxlength="30" required>
	<label for="web">Web</label>
	<input id="web" name="web" type="url" value="<?=h($form["web"] ?? "")?>" maxlength="100" required>
	<div class="buttons">
		<button><?=($author ? "Update" : "Insert")?></button>
		<?php if ($author) { ?><button class="delete" name="delete" value="1" formnovalidate>Delete</button><?php } ?>
	</div>
</form>
<?php
page_footer($log, $cache);
