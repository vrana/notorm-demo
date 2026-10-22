-- The tables of NotORM's tests/software.sql for SQLite, the rows are in data.sql.
-- The ids are INTEGER PRIMARY KEY, an alias of the rowid, so that the demo can insert rows.
-- SQLite checks the foreign keys only with PRAGMA foreign_keys = ON, bootstrap.inc.php sets it; it doesn't check the lengths.

DROP TABLE IF EXISTS application_tag;

DROP TABLE IF EXISTS application;

DROP TABLE IF EXISTS tag;

DROP TABLE IF EXISTS author;

DROP TABLE IF EXISTS category;

DROP TABLE IF EXISTS setting;

DROP TABLE IF EXISTS notorm;

CREATE TABLE author (
  id INTEGER NOT NULL,
  name varchar(30) NOT NULL,
  web varchar(100) NOT NULL,
  born date DEFAULT NULL,
  PRIMARY KEY (id)
);

CREATE INDEX author_name ON author (name);

CREATE TABLE tag (
  id INTEGER NOT NULL,
  name varchar(20) NOT NULL,
  PRIMARY KEY (id)
);

CREATE INDEX tag_name ON tag (name);

CREATE TABLE application (
  id INTEGER NOT NULL,
  author_id int NOT NULL,
  maintainer_id int,
  title varchar(50) NOT NULL,
  web varchar(100),
  slogan varchar(100) NOT NULL,
  logo blob,
  PRIMARY KEY (id),
  CONSTRAINT application_author FOREIGN KEY (author_id) REFERENCES author (id),
  CONSTRAINT application_maintainer FOREIGN KEY (maintainer_id) REFERENCES author (id)
);

CREATE INDEX application_title ON application (title);

CREATE TABLE application_tag (
  application_id int NOT NULL,
  tag_id int NOT NULL,
  PRIMARY KEY (application_id, tag_id),
  CONSTRAINT application_tag_tag FOREIGN KEY (tag_id) REFERENCES tag (id),
  CONSTRAINT application_tag_application FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE
);

CREATE TABLE category (
  id INTEGER NOT NULL,
  name varchar(30) NOT NULL,
  parent_id int,
  PRIMARY KEY (id),
  CONSTRAINT category_parent FOREIGN KEY (parent_id) REFERENCES category (id)
);

CREATE INDEX category_name ON category (name);

CREATE TABLE setting (
  site_name varchar(50) NOT NULL,
  per_page int NOT NULL
);

CREATE TABLE notorm (
  id varchar(255) NOT NULL,
  data text NOT NULL,
  PRIMARY KEY (id)
);
