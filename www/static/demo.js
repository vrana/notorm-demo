jush.create_links = false;
jush.style('static/jush/jush.css');
jush.style('static/jush/jush-dark.css', '(prefers-color-scheme: dark)');
jush.highlight_tag('code', 0); // keep tabs, their width is set by CSS tab-size

// the database and cache switches in the header are sent right after a change
for (const select of document.querySelectorAll('header .switch select')) {
	select.addEventListener('change', event => event.target.form.submit());
}

// move each query next to the block of <main> showing its data, QueryLog::mark() put <template data-query> where the data start
const main = document.querySelector('main');
const isBlock = element => element && element.tagName != 'TEMPLATE' && !element.classList.contains('queries');
for (const marker of main.querySelectorAll('template[data-query]')) {
	let block = marker;
	while (block.parentElement != main) {
		block = block.parentElement;
	}
	if (block == marker) { // between the blocks, e.g. the queries run before the page printed anything
		do {
			block = block.previousElementSibling;
		} while (block && !isBlock(block));
	}
	let queries = block.previousElementSibling;
	if (!queries || !queries.classList.contains('queries')) {
		queries = document.createElement('div');
		queries.className = 'queries';
		block.before(queries); // before, so that the float starts at the top of the block
	}
	queries.append(document.getElementById('query-' + marker.dataset.query));
}
