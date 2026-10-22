<?php declare(strict_types=1);

/** Cache shared by the databases with the keys of each database apart, e.g. the sequence of PostgreSQL would break lastInsertId() in MS SQL */
class CacheByDatabase implements NotORM\Cache {

	function __construct(
		public NotORM\Cache $cache,
		private string $database,
	) {
	}

	function load(string $key): mixed {
		return $this->cache->load("$this->database;$key");
	}

	function save(string $key, mixed $data): void {
		$this->cache->save("$this->database;$key", $data);
	}

}
