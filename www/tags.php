<?php declare(strict_types=1);
require __DIR__ . "/../include/bootstrap.inc.php";

$notice = "";
if ($_POST) {
	$notice = demo_write($db, $log, function () use ($db) {
		$tag = $db->tag()->insert(["name" => $_POST["name"]]);
		return "Inserted tag $tag[id]";
	});
}

page_header("Tags", $notice);
?>
<table>
<thead><tr><th>Name<th class="number">Applications</thead>
<?php foreach ($db->tag()->order("name") as $tag) { ?>
<tr>
	<td data-label="Name"><?=a("tag.php?id=$tag[id]", $tag["name"])?>
	<td data-label="Applications" class="number"><?=a("./?tag=$tag[id]", $tag->application_tag()->count("*"))?>
<?php } ?>
</table>

<h2>New tag</h2>
<form class="inline" method="post">
	<input name="name" value="<?=h($_POST["name"] ?? "")?>" maxlength="20" required aria-label="Name">
	<button>Insert</button>
</form>
<?php
page_footer($log, $cache);
