# NotORM demo

The source of [demo.notorm.com](https://demo.notorm.com/), a small site built with [NotORM](https://www.notorm.com/) over the `software` database from NotORM's tests. Every page lists the queries it ran, each with the NotORM code that ran it, the SQL and the time, and links the line to its source, on GitHub by default (`source` in `config.php`).

- **Applications** (`index.php`): search, ordering by a joined table, a tag filter and pagination. Authors, maintainers and tags of all listed applications are loaded in one query each, never one per row.
- **Application, author, tag**: the detail with the referencing rows (`via()` for the second foreign key to the same table), and forms to insert, update and delete.
- **Categories**: a tree from a self-referencing table, one query per level.

The demo is read-only. A write runs for real in a transaction, so its queries and their time are real, and is then rolled back.

## Running it

Needs PHP 8.0+ with Composer and PDO for the databases: MySQL or MariaDB, PostgreSQL, SQLite, MS SQL (`pdo_sqlsrv`), Oracle (`pdo_oci`).

```bash
composer install
```

`composer install` also builds `www/static/jush/` from [JUSH](https://jush.sourceforge.io/) with only the modules the demo uses (`build-jush.php`).

```bash
cp config.example.php config.php
```

Fill in the databases in `config.php`: `databases` maps a name to the DSN, username and password. Visitors switch between them in the top right corner, the first one is the default. Then create the tables and the rows in each of them, here with an account that may create tables:

```bash
php setup.php MySQL root password
```

`setup.php` takes the tables from `sql/mysql.sql`, `sql/pgsql.sql`, `sql/sqlite.sql`, `sql/sqlsrv.sql` or `sql/oci.sql` by the PDO driver and the rows from `sql/data.sql`, the same in all databases.

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

-- PostgreSQL, as a superuser; the grants on the tables again after each php setup.php PostgreSQL
CREATE DATABASE notorm_demo;
CREATE ROLE notorm_demo LOGIN PASSWORD '…';
GRANT CONNECT ON DATABASE notorm_demo TO notorm_demo;
\c notorm_demo
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO notorm_demo;
GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO notorm_demo;
```

With SQLite, the web server needs to write the database file and its directory, where SQLite creates the journal of the transaction.

In MS SQL, an administrator creates the database and makes the login its `db_owner`. In Oracle, a database is a user with its schema, the web account is the owner of the tables:

```sql
-- Oracle, as a DBA
CREATE USER notorm_demo IDENTIFIED BY "…" QUOTA UNLIMITED ON USERS;
GRANT CREATE SESSION, CREATE TABLE, CREATE SEQUENCE TO notorm_demo; -- a sequence for each identity column
```

NotORM can't read the id of an inserted row in Oracle, PDO_OCI doesn't implement `lastInsertId()`. The notices then show no id and inserting an application with tags fails.

The forms send what the visitor typed and the database validates it, so the server needs `STRICT_TRANS_TABLES` in `sql_mode` (the default since MySQL 5.7 and MariaDB 10.2.4); without it, too long values would be cut silently. PostgreSQL, MS SQL and Oracle check the lengths too, SQLite doesn't.

Writes are rolled back, but MySQL doesn't roll back AUTO_INCREMENT counters and the other databases their sequences and identities (except SQLite), so the ids of the inserted rows keep growing there.

The cache remembers the used columns and the foreign keys read from the database. Visitors switch it in the top right corner between the `NotORM\Cache` classes listed in `caches` in `config.php` with their constructor arguments (or a function returning them, called with the PDO connection only for the chosen cache), `""` for off, the first one is the default: `APCu` (shared, needs the extension), `File` (shared), `Session` (every visitor starts empty, so the first visit of a page shows `SELECT *` and a reload only the used columns), `IncludeFile`, `Database` (the `notorm` table, its queries aren't logged) and off (always `SELECT *`, and the foreign keys are read on every request, outside the query log).

## License

Apache License 2.0 or GPL 2 or later, the same as NotORM.
