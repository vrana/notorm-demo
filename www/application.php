<?php declare(strict_types=1);
require __DIR__ . "/../include/bootstrap.inc.php";

$id = $_GET["id"] ?? ""; // no id for a new row
$notice = "";
if ($_POST) {
	$data = [
		"title" => $_POST["title"],
		"author_id" => $_POST["author_id"],
		"maintainer_id" => ($_POST["maintainer_id"] ?: null),
		"web" => ($_POST["web"] ?: null),
		"slogan" => $_POST["slogan"],
	];
	$tags = array_map(fn($tag_id) => ["tag_id" => $tag_id], $_POST["tags"] ?? []); // no tags[] without a checked checkbox
	$notice = demo_write($software, $log, function () use ($software, $id, $data, $tags) {
		if (isset($_POST["delete"])) {
			$deleted = $software->application("id", $id)->delete(); // ON DELETE CASCADE deletes its application_tag rows
			return "Deleted " . plural($deleted, "application");
		}
		if (!$id) {
			$application = $software->application()->insert($data);
			$application->application_tag()->insertMulti($tags);
			return "Inserted application $application[id] and its tags";
		}
		$application = $software->application[$id];
		$updated = $application->update($data);
		$application->application_tag()->delete();
		$application->application_tag()->insertMulti($tags);
		return "Updated " . plural($updated, "application") . " and the tags";
	});
}

$application = null;
$tags = [];
if ($id) {
	$application = $software->application[$id];
	if (!$application) {
		not_found($log, $cache);
	}
	foreach ($application->application_tag() as $application_tag) {
		$tags[$application_tag["tag_id"]] = $application_tag->tag;
	}
}

$authors = $software->author()->order("name"); // used twice, queried once
$form = ($_POST ? $data : $application); // after a POST, show what was sent
$checked = ($_POST ? array_flip($_POST["tags"] ?? []) : $tags);

page_header($application["title"] ?? "New application", $notice);
if ($application) {
	?>
<dl>
	<dt>Slogan<dd><?=h($application["slogan"])?>
	<dt>Web<dd><?=($application["web"] ? a($application["web"], $application["web"]) : "")?>
	<dt>Author<dd><?=a("author.php?id=$application[author_id]", $application->author["name"])?>
	<dt>Maintainer<dd><?=($application->maintainer ? a("author.php?id=$application[maintainer_id]", $application->maintainer["name"]) : "")?>
	<dt>Tags<dd><?php foreach ($tags as $tag) { ?><a class="tag" href="tag.php?id=<?=$tag["id"]?>"><?=h($tag["name"])?></a><?php } ?>
</dl>

<h2>Edit</h2>
<?php
}
?>
<form class="edit" method="post">
	<label for="title">Title</label>
	<input id="title" name="title" value="<?=h($form["title"] ?? "")?>" maxlength="50" required>
	<label for="slogan">Slogan</label>
	<input id="slogan" name="slogan" value="<?=h($form["slogan"] ?? "")?>" maxlength="100" required>
	<label for="web">Web</label>
	<input id="web" name="web" type="url" value="<?=h($form["web"] ?? "")?>" maxlength="100">
	<label for="author_id">Author</label>
	<select id="author_id" name="author_id" required><?=($application ? "" : "<option value=''></option>")?>
  <?=options($authors, "name", $form["author_id"] ?? "")?>
  </select>
	<label for="maintainer_id">Maintainer</label>
	<select id="maintainer_id" name="maintainer_id"><option value=""></option><?=options($authors, "name", $form["maintainer_id"] ?? "")?></select>
	<span>Tags</span>
	<div class="tags">
	<?php foreach ($software->tag()->order("name") as $tag) { ?>
		<label><input type="checkbox" name="tags[]" value="<?=$tag["id"]?>"<?=(isset($checked[$tag["id"]]) ? " checked" : "")?>> <?=h($tag["name"])?></label>
	<?php } ?>
	</div>
	<div class="buttons">
		<button><?=($application ? "Update" : "Insert")?></button>
		<?php if ($application) { ?><button class="delete" name="delete" value="1" formnovalidate>Delete</button><?php } ?>
	</div>
</form>
<?php
page_footer($log, $cache);
