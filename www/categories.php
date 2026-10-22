<?php declare(strict_types=1);
require __DIR__ . "/../include/bootstrap.inc.php";

/** Print categories with their subcategories, one query per level
* @param iterable<NotORM\Row> $categories
*/
function tree(iterable $categories): void {
	echo "<ul class='tree'>\n";
	foreach ($categories->order("name") as $category) {
		echo "<li>" . h($category["name"]) . "\n";
		tree($category->category()); // category.parent_id references category.id
	}
	echo "</ul>\n";
}

page_header("Categories");
tree($db->category("parent_id", null));
page_footer($log, $cache);
