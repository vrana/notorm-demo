<?php declare(strict_types=1);

// Copy to config.php, which is not in the repository.
return [
	"dsn" => "mysql:host=localhost;dbname=notorm_demo;charset=utf8mb4",
	"username" => "notorm_demo",
	"password" => "",
	"caches" => [ // class => constructor arguments or a function returning them called only for the chosen cache, the visitors can switch between them, the first one is the default
		NotORM\Cache\File::class => [sys_get_temp_dir() . "/notorm-demo.dat"],
		NotORM\Cache\IncludeFile::class => [sys_get_temp_dir() . "/notorm-demo.php"],
		NotORM\Cache\Database::class => fn(PDO $connection) => [$connection], // the notorm table
		NotORM\Cache\Session::class => [],
		//~ NotORM\Cache\APCu::class => [],
		//~ NotORM\Cache\Memcache::class => fn() => [memcache_connect("localhost")],
		//~ NotORM\Cache\Memcached::class => function () { $memcached = new Memcached; $memcached->addServer("localhost", 11211); return [$memcached]; },
		"" => [], // off
	],
	"source" => 'https://github.com/vrana/notorm-demo/blob/main/www/$file#L$line', // link of the code which ran a query, $file is relative to www/
];
