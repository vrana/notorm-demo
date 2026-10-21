# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

The public demo of NotORM at demo.notorm.com. NotORM itself is the Composer dependency `vrana/notorm` (its source is in the sibling `../../notorm` checkout); this repo has only the demo. Setup and deployment are in `README.md`: `composer install`, `config.php` from `config.example.php` (gitignored, the repo is public), `php setup.php` to recreate the tables from `sql/software.sql`, document root `www/`. Locally `php -S 127.0.0.1:8000 -t www` with MySQL `ODBC`/`ODBC` and the database `notorm_demo`.

Each table in `sql/software.sql` has its own hundred of ids (author 1xx, tag 2xx, application 3xx, category 4xx), so a wrong table in a query finds nothing instead of an unrelated row. Keep it so when adding rows.

Code follows NotORM's idiom: `<?php declare(strict_types=1);`, tabs, `function` without visibility, native types, PHP 8.0 as the minimum (no `never`, `readonly`, enums; no mbstring, which isn't loaded in the local PHP 8.0). Check changes with PHP 8.0 (`C:\Program Files\PHP80\php.exe`) and the latest PHP; before 8.1, PDO MySQL returns numbers as strings, so cast them where a native type is required.

## How it works

- `www/*.php` are the pages, one file per page with the id in the query string (`application.php?id=301`). Each requires `include/bootstrap.inc.php`, handles its POST, then calls `page_header()` and `page_footer()` from `include/layout.inc.php`. The query log quotes the pages and links to them on GitHub, so keep them short and readable and keep the NotORM calls in them, not in helpers.
- `include/QueryLog.php` is set as NotORM's `debug` and `debugTimer` callbacks. For each query it finds the first stack frame outside NotORM and `include/` and shows the PHP statement around that line, found by `PhpToken` (split at `;`, `{`, `}` and PHP tags). Results are lazy, so this is where the rows were first read, e.g. the `foreach`, not where the query was built. When one line has several statements, the ones with `->` win.
- Each query is shown next to the data it loaded. `bootstrap.inc.php` starts an output buffer, `QueryLog::start()` remembers its length, and `page_footer()` lets `QueryLog::mark()` put `<template data-query="N">` there: moved before `<` when inside a tag, and after the title or notice for the queries run before the page printed anything. `<template>` is allowed even in `<table>` and `<select>`, so the browser keeps it in place. `demo.js` then moves each `.query` card into a `.queries` group right before the top-level block of `<main>` holding its marker. CSS floats the groups right with `clear: right` on wide screens (so they start at the top of their block and push each other down, without gaps on the left) and leaves them above their block on narrow ones. Without JS, the cards stay listed after `<main>`. The location links to `source` from `config.php`, a URL with `$file` (relative to `www/`) and `$line`: GitHub's `main` branch in `config.example.php`, which matches only the pushed code, an `editor://` URL locally.
- Writes go through `demo_write()` in `include/functions.inc.php`: `BEGIN`, the closure with the NotORM writes, always `ROLLBACK`. PDO errors (e.g. deleting an author who has applications) become the notice. Don't add anything that commits.
- The cache is the visitor's choice: the `<select>` in `page_header()` posts `cache` on change (`demo.js`), `bootstrap.inc.php` stores it in a cookie and redirects back with 303 before the page sees the POST, so it never reaches the pages' `if ($_POST)` writes. With Session chosen, the switch also offers `CLEAR!` (`clear`), which empties `$_SESSION["NotORM"]` and keeps the cookie. `config.php` lists the allowed `NotORM\Cache` classes with their constructor arguments in `caches` (the first one is the default; a `Closure` instead of the arguments is called with the PDO connection only for the chosen cache, e.g. `Database` or a Memcached connection), `""` is off (stored as `off` in the cookie, which can't be empty), the constant `CACHES` has their short names, `cache_choice()` reads the cookie and the same cache serves the columns and Discovery's foreign keys.
- CSRF protection is central in `include/bootstrap.inc.php`: a POST with `Sec-Fetch-Site` other than `same-origin` gets 403. A missing header is allowed (curl, old browsers), because a CSRF victim's browser always sends it.
- The structure is `NotORM\Structure\Discovery` with `%s_id`, so `$application->maintainer` finds the table `author` from the foreign key. Two foreign keys of `application` point to `author`, so `$author->application()` always needs `->via("author_id")` or `->via("maintainer_id")`.
- `build-jush.php` (run by Composer) concatenates only the JUSH modules in its `$modules` into `www/static/jush/jush.js`; add a module there when highlighting another language (e.g. `jush-pgsql` for PostgreSQL).

## Repository conventions

- Commit subjects are short imperative sentences. Files are committed with LF line endings.
- Never push; Jakub reviews local commits first.
