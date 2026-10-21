<?php declare(strict_types=1);
require __DIR__ . "/../include/bootstrap.inc.php";

$id = $_GET["id"];
$notice = "";
if ($_POST) {
	$notice = demo_write($software, $log, function () use ($software, $id) {
		if (isset($_POST["delete"])) {
			$deleted = $software->tag("id", $id)->delete(); // fails if an application has this tag
			return "Deleted " . plural($deleted, "tag");
		}
		$updated = $software->tag("id", $id)->update(["name" => $_POST["name"]]);
		return "Updated " . plural($updated, "tag");
	});
}

$tag = $software->tag[$id];
if (!$tag) {
	not_found($log, $cache);
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
