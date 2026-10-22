<?php declare(strict_types=1);
require __DIR__ . "/../include/bootstrap.inc.php";

$tag = $db->tag[$_GET["id"]];
if (!$tag) {
	not_found($log, $cache);
}

if ($_POST) {
	$notice = demo_write($db, $log, function () use ($tag) {
		if (isset($_POST["delete"])) {
			$deleted = $tag->delete(); // fails if an application has this tag
			return ["Deleted " . plural($deleted, "tag"), "tags.php"];
		}
		$updated = $tag->update(["name" => $_POST["name"]]); // the row keeps its values, the page shows them after the rollback
		return ["Updated " . plural($updated, "tag"), "tag.php?id=$tag[id]"];
	});
}

page_header($tag["name"], $notice);
?>
<h2>Applications</h2>
<ul>
<?php foreach ($tag->application_tag() as $application_tag) { ?>
	<li><?=a("application.php?id=$application_tag[application_id]", $application_tag->application["title"])?>
		<small><?=h($application_tag->application["slogan"])?></small>
<?php } ?>
</ul>

<h2>Edit</h2>
<form class="edit" method="post">
	<label for="name">Name</label>
	<input id="name" name="name" value="<?=h($_POST ? $_POST["name"] : $tag["name"])?>" maxlength="20" required>
	<div class="buttons">
		<button>Update</button>
		<button class="delete" name="delete" value="1" formnovalidate>Delete</button>
	</div>
</form>
<?php
page_footer($log, $cache);
