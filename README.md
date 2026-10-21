# NotORM demo

The source of [demo.notorm.com](https://demo.notorm.com/), a small site built with [NotORM](https://www.notorm.com/) over the `software` database from NotORM's tests. Every page lists the queries it ran, each with the NotORM code that ran it, the SQL and the time, and links the line to its source, on GitHub by default (`source` in `config.php`).

- **Applications** (`index.php`): search, ordering by a joined table, a tag filter and pagination. Authors, maintainers and tags of all listed applications are loaded in one query each, never one per row.
- **Application, author, tag**: the detail with the referencing rows (`via()` for the second foreign key to the same table), and forms to insert, update and delete.
- **Categories**: a tree from a self-referencing table, one query per level.

The demo is read-only. A write runs for real in a transaction, so its queries and their time are real, and is then rolled back.

## Running it

Needs PHP 8.0+ with PDO MySQL and Composer.

```bash
composer install
```

`composer install` also builds `www/static/jush/` from [JUSH](https://jush.sourceforge.io/) with only the modules the demo uses (`build-jush.php`).

```bash
cp config.example.php config.php
```

Fill in the database and the account in `config.php`, then create the tables and the data, here with an account that may create tables:

```bash
php setup.php root password
```

The document root is `www/`. Locally:

```bash
php -S 127.0.0.1:8000 -t www
```

## Production

The web account needs only these rights; `setup.php` is run by an administrator:

```sql
CREATE DATABASE notorm_demo CHARACTER SET utf8mb4;
CREATE USER 'notorm_demo'@'localhost' IDENTIFIED BY '…';
GRANT SELECT, INSERT, UPDATE, DELETE ON notorm_demo.* TO 'notorm_demo'@'localhost';
```

The forms send what the visitor typed and the database validates it, so the server needs `STRICT_TRANS_TABLES` in `sql_mode` (the default since MySQL 5.7 and MariaDB 10.2.4); without it, too long values would be cut silently.

Writes are rolled back, but MySQL doesn't roll back AUTO_INCREMENT counters, so the ids of the inserted rows keep growing.

The cache remembers the used columns and the foreign keys read from the database. Visitors switch it in the top right corner between the `NotORM\Cache` classes listed in `caches` in `config.php` with their constructor arguments (or a function returning them, called with the PDO connection only for the chosen cache), `""` for off, the first one is the default: `APCu` (shared, needs the extension), `File` (shared), `Session` (every visitor starts empty, so the first visit of a page shows `SELECT *` and a reload only the used columns), `IncludeFile`, `Database` (the `notorm` table, its queries aren't logged) and off (always `SELECT *`, and the foreign keys are read on every request, outside the query log).

## License

Apache License 2.0 or GPL 2 or later, the same as NotORM.
