<?php declare(strict_types=1);
require __DIR__ . "/../include/bootstrap.inc.php";

/** Print categories with their subcategories
* @param array<int|string, list<NotORM\Row>> $children parent id => categories, "" for no parent
*/
function tree(array $children, int|string $parent): void {
	echo "<ul class='tree'>\n";
	foreach ($children[$parent] as $category) {
		echo "<li>" . h($category["name"]) . "\n";
		if (isset($children[$category["id"]])) {
			tree($children, $category["id"]);
		}
	}
	echo "</ul>\n";
}

page_header("Categories");
$children = [];
foreach ($db->category()->order("name") as $category) { // all levels in one query
	$children[$category["parent_id"] ?? ""][] = $category; // category.parent_id references category.id
}
tree($children, "");
page_footer($log, $cache);
