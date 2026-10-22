<?php declare(strict_types=1);
require __DIR__ . "/../include/bootstrap.inc.php";

page_header("Authors");
?>
<p><a class="button" href="author.php">New author</a></p>

<table>
<thead><tr><th>Name<th>Web<th class="number">Authored<th class="number">Maintained</thead>
<?php foreach ($db->author()->order("name") as $author) { ?>
<tr>
	<td data-label="Name"><?=a("author.php?id=$author[id]", $author["name"])?>
	<td data-label="Web"><?=a($author["web"], $author["web"])?>
	<td data-label="Authored" class="number"><?=$author->application()->via("author_id")->count("*")?>
	<td data-label="Maintained" class="number"><?=$author->application()->via("maintainer_id")->count("*")?>
<?php } ?>
</table>
<?php
page_footer($log, $cache);
