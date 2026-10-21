-- The structure of NotORM's tests/software.sql with more rows, for MySQL.
-- The ids are AUTO_INCREMENT so that the demo can insert rows.
-- Each table has its own hundred (author 1xx, tag 2xx, application 3xx, category 4xx), so an id never matches a row of another table by mistake.
-- Every statement ends with ";" at the end of a line, setup.php splits the file there.

DROP TABLE IF EXISTS application_tag, application, tag, author, category, setting, notorm;

CREATE TABLE author (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(30) NOT NULL,
  web varchar(100) NOT NULL,
  born date DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO author (id, name, web) VALUES
(101, 'Jakub Vrana', 'https://www.vrana.cz/'),
(102, 'David Grudl', 'https://davidgrudl.com/'),
(103, 'Fabien Potencier', 'https://fabien.potencier.org/'),
(104, 'Taylor Otwell', 'https://laravel.com/'),
(105, 'Sebastian Bergmann', 'https://sebastian-bergmann.de/'),
(106, 'Jordi Boggiano', 'https://seld.be/'),
(107, 'Nikita Popov', 'https://www.npopov.com/'),
(108, 'Rasmus Lerdorf', 'https://toys.lerdorf.com/'),
(109, 'Michael Widenius', 'https://mariadb.org/'),
(110, 'John Resig', 'https://johnresig.com/'),
(111, 'Evan You', 'https://evanyou.me/'),
(112, 'Nils Adermann', 'https://naderman.de/'),
(113, 'Ryan Dahl', 'https://tinyclouds.org/'),
(114, 'D. Richard Hipp', 'https://www.hwaci.com/drh/');

CREATE TABLE tag (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(20) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tag (id, name) VALUES
(201, 'PHP'),
(202, 'MySQL'),
(203, 'JavaScript'),
(204, 'Framework'),
(205, 'Testing'),
(206, 'Templates'),
(207, 'Database'),
(208, 'CLI'),
(209, 'Debugging'),
(210, 'Parser');

CREATE TABLE application (
  id int NOT NULL AUTO_INCREMENT,
  author_id int NOT NULL,
  maintainer_id int,
  title varchar(50) NOT NULL,
  web varchar(100),
  slogan varchar(100) NOT NULL,
  logo blob,
  PRIMARY KEY (id),
  CONSTRAINT application_author FOREIGN KEY (author_id) REFERENCES author (id),
  CONSTRAINT application_maintainer FOREIGN KEY (maintainer_id) REFERENCES author (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX application_title ON application (title);

INSERT INTO application (id, author_id, maintainer_id, title, web, slogan) VALUES
(301, 101, 101, 'Adminer', 'https://www.adminer.org/', 'Database management in a single PHP file'),
(302, 101, NULL, 'JUSH', 'https://jush.sourceforge.io/', 'JavaScript Syntax Highlighter'),
(303, 102, 102, 'Nette', 'https://nette.org/', 'Comfortable and Safe Web Development in PHP'),
(304, 102, 102, 'Dibi', 'https://dibi.nette.org/', 'Dibi - smart database abstraction layer'),
(305, 101, 101, 'NotORM', 'https://www.notorm.com/', 'Simple reading data from the database'),
(307, 102, 102, 'Tracy', 'https://tracy.nette.org/', 'Debugging tool for PHP'),
(308, 102, 102, 'Latte', 'https://latte.nette.org/', 'Safe templating system for PHP'),
(309, 102, NULL, 'Texy', 'https://texy.info/', 'Humane text to HTML converter'),
(310, 103, 103, 'Symfony', 'https://symfony.com/', 'High performance PHP framework for web development'),
(311, 103, 103, 'Twig', 'https://twig.symfony.com/', 'The flexible, fast and secure template engine for PHP'),
(312, 103, NULL, 'Silex', NULL, 'PHP micro-framework based on the Symfony components'),
(313, 104, 104, 'Laravel', 'https://laravel.com/', 'The PHP framework for web artisans'),
(314, 104, 104, 'Lumen', 'https://lumen.laravel.com/', 'Fast micro-framework by Laravel'),
(315, 105, 105, 'PHPUnit', 'https://phpunit.de/', 'The PHP testing framework'),
(316, 105, 105, 'php-code-coverage', 'https://github.com/sebastianbergmann/php-code-coverage', 'Collection, processing and rendering of code coverage information'),
(317, 106, 112, 'Composer', 'https://getcomposer.org/', 'Dependency manager for PHP'),
(318, 106, 106, 'Monolog', 'https://github.com/Seldaek/monolog', 'Logging for PHP'),
(319, 106, 106, 'Packagist', 'https://packagist.org/', 'The PHP package repository'),
(320, 107, 107, 'PHP-Parser', 'https://github.com/nikic/PHP-Parser', 'A PHP parser written in PHP'),
(321, 108, NULL, 'PHP', 'https://www.php.net/', 'A popular general-purpose scripting language'),
(322, 109, NULL, 'MySQL', 'https://www.mysql.com/', 'Open source relational database'),
(323, 109, 109, 'MariaDB', 'https://mariadb.org/', 'The open source relational database'),
(324, 110, NULL, 'jQuery', 'https://jquery.com/', 'Write less, do more'),
(325, 111, 111, 'Vue.js', 'https://vuejs.org/', 'The progressive JavaScript framework'),
(326, 111, 111, 'Vite', 'https://vite.dev/', 'Next generation frontend tooling'),
(327, 113, NULL, 'Node.js', 'https://nodejs.org/', 'JavaScript runtime built on V8'),
(328, 113, 113, 'Deno', 'https://deno.com/', 'The open-source JavaScript runtime for the modern web'),
(329, 114, 114, 'SQLite', 'https://sqlite.org/', 'Small, fast, self-contained SQL database engine'),
(330, 114, 114, 'Fossil', 'https://fossil-scm.org/', 'Simple, high-reliability, distributed version control'),
(331, 112, 112, 'Private Packagist', 'https://packagist.com/', 'Composer repository for private packages');

CREATE TABLE application_tag (
  application_id int NOT NULL,
  tag_id int NOT NULL,
  PRIMARY KEY (application_id, tag_id),
  CONSTRAINT application_tag_tag FOREIGN KEY (tag_id) REFERENCES tag (id),
  CONSTRAINT application_tag_application FOREIGN KEY (application_id) REFERENCES application (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO application_tag (application_id, tag_id) VALUES
(301, 201), (301, 202), (301, 207),
(302, 203),
(303, 201), (303, 204),
(304, 201), (304, 202), (304, 207),
(305, 201), (305, 207),
(307, 201), (307, 209),
(308, 201), (308, 206),
(309, 201),
(310, 201), (310, 204),
(311, 201), (311, 206),
(312, 201), (312, 204),
(313, 201), (313, 204),
(314, 201), (314, 204),
(315, 201), (315, 205),
(316, 201), (316, 205),
(317, 201), (317, 208),
(318, 201), (318, 209),
(319, 201),
(320, 201), (320, 210),
(321, 201),
(322, 202), (322, 207),
(323, 202), (323, 207),
(324, 203),
(325, 203), (325, 204),
(326, 203), (326, 208),
(327, 203), (327, 208),
(328, 203), (328, 208),
(329, 207),
(330, 208),
(331, 201);

CREATE TABLE category (
  id int NOT NULL AUTO_INCREMENT,
  name varchar(30) NOT NULL,
  parent_id int,
  PRIMARY KEY (id),
  CONSTRAINT category_parent FOREIGN KEY (parent_id) REFERENCES category (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO category (id, name, parent_id) VALUES
(401, 'Database', NULL),
(402, 'Web', NULL),
(403, 'Administration', 401),
(404, 'Library', 401),
(405, 'Framework', 402),
(406, 'Development', NULL),
(407, 'Testing', 406),
(408, 'Debugging', 406),
(409, 'Package management', 406),
(410, 'Templates', 402),
(411, 'Engine', 401),
(412, 'Code coverage', 407);

CREATE TABLE setting (
  site_name varchar(50) NOT NULL,
  per_page int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO setting (site_name, per_page) VALUES ('Software', 10);

-- NotORM\Cache\Database
CREATE TABLE notorm (
  id varchar(255) NOT NULL,
  data text NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
