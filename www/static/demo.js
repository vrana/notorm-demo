jush.create_links = false;
jush.style('static/jush/jush.css');
jush.style('static/jush/jush-dark.css', '(prefers-color-scheme: dark)');
jush.highlight_tag('code', 0); // keep tabs, their width is set by CSS tab-size

// the database and cache switches in the header are sent right after a change
for (const select of document.querySelectorAll('header .switch select')) {
	select.addEventListener('change', event => event.target.form.submit());
}

// QueryLog::mark() put <template data-query> where the data of each query start
// wide screens: a link #N there and the query on the right next to the block of <main> with the data
// narrow screens: the query itself there
const main = document.querySelector('main');
const wide = matchMedia('(min-width: 1024px)'); // the same as in demo.css
const isBlock = element => element.tagName != 'TEMPLATE' && !element.classList.contains('queries');

// the place for a link or a query: the marker or the nearest place which may contain them
function insertionPoint(marker) {
	const inline = marker.parentElement.closest('select, a, button'); // no queries and links inside
	if (inline) {
		const label = (inline.previousElementSibling?.tagName == 'LABEL' ? inline.previousElementSibling : inline); // keep the label with its field
		return [inline.parentElement, label];
	}
	if (/^(TABLE|THEAD|TBODY|TR|UL|OL)$/.test(marker.parentElement.tagName)) {
		let next = marker.nextElementSibling;
		while (next && next.tagName == 'TEMPLATE') {
			next = next.nextElementSibling;
		}
		if (next && /^(THEAD|TBODY)$/.test(next.tagName)) { // e.g. the implicit <tbody> around the rows
			next = next.firstElementChild;
		}
		if (next && /^(TR|TD|TH|LI)$/.test(next.tagName)) { // the data start in the next row or item
			const cell = (next.tagName == 'TR' ? next.firstElementChild : next);
			return [cell, cell.firstChild];
		}
		const list = marker.parentElement.closest('table, ul, ol');
		return [list.parentElement, list];
	}
	return [marker.parentElement, marker];
}

// the block of <main> with the data, the queries run before the page printed anything are before the title or the notice
function blockOf(marker) {
	let block = marker;
	while (block.parentElement != main) {
		block = block.parentElement;
	}
	while (!isBlock(block)) {
		block = block.nextElementSibling;
	}
	return block;
}

const queries = [];
for (const marker of main.querySelectorAll('template[data-query]')) {
	const card = document.getElementById('query-' + marker.dataset.query);
	const [parent, before] = insertionPoint(marker);
	const link = document.createElement('a');
	link.className = 'query-link';
	link.href = '#' + card.id;
	link.textContent = '#' + marker.dataset.query;
	link.addEventListener('mouseenter', () => card.classList.add('highlight'));
	link.addEventListener('mouseleave', () => card.classList.remove('highlight'));
	link.addEventListener('click', event => {
		event.preventDefault();
		card.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
		card.classList.add('highlight');
		setTimeout(() => card.classList.remove('highlight'), 1500);
	});
	card.addEventListener('mouseenter', () => link.classList.add('highlight'));
	card.addEventListener('mouseleave', () => link.classList.remove('highlight'));
	if (before && before.tagName == 'LABEL') { // a link in the label, not in the grid of the form
		before.append(' ', link);
	} else if (marker.parentElement == main) { // in the title or the notice
		const block = blockOf(marker);
		const links = block.querySelectorAll(':scope > .query-link');
		if (links.length) {
			links[links.length - 1].after(' ', link);
		} else {
			block.prepend(link, ' ');
		}
	} else {
		parent.insertBefore(link, before);
		link.after(' ');
	}
	const block = blockOf(marker);
	let group = block.previousElementSibling;
	if (!group || !group.classList.contains('queries')) {
		group = document.createElement('div');
		group.className = 'queries';
		block.before(group); // before, so that the float starts at the top of the block
	}
	queries.push({ card, group, parent, before });
}

let isWide;
function layout() {
	if (wide.matches === isWide) {
		return;
	}
	isWide = wide.matches;
	for (const { card, group, parent, before } of queries) {
		if (isWide) {
			group.append(card);
		} else {
			parent.insertBefore(card, before);
		}
	}
}
wide.addEventListener('change', layout);
addEventListener('resize', layout); // the change event doesn't come e.g. in an emulated viewport
layout();
