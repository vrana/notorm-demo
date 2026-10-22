<?php declare(strict_types=1);

/** Log of the executed queries with the NotORM code which caused them and their time */
class QueryLog {
	/** @var list<array{query: string, parameters: mixed[], file: string, line: int, offset: int, time: float}> */ private array $entries = [];
	private int $start = 0;
	/** @var list<string> directories whose frames are skipped when looking for the code which caused the query */ private array $skip;
	/** @var array<string, list<array{int, int, string}>> file => [first line, last line, code] */ private array $statements = [];
	/** @var array<int, true> entries reported by the notice, see mark() */ private array $notice = [];

	/** @param string $source URL of the code which caused a query with $file relative to www/ and $line */
	function __construct(
		private \PDO $pdo,
		private string $source,
	) {
		$this->skip = [
			dirname((string) (new \ReflectionClass(\NotORM\NotORM::class))->getFileName()) . DIRECTORY_SEPARATOR,
			__DIR__ . DIRECTORY_SEPARATOR,
		];
	}

	/** Set the NotORM callbacks */
	function attach(\NotORM\NotORM $notORM): void {
		$notORM->debug = [$this, 'start'];
		$notORM->debugTimer = [$this, 'stop'];
	}

	/** NotORM debug callback called before executing a query
	* @param mixed[] $parameters
	*/
	function start(string $query, array $parameters): void {
		[$file, $line] = $this->caller();
		$offset = (int) ob_get_length(); // the page printed so far, the data of this query follow
		$this->entries[] = ["query" => $query, "parameters" => $parameters, "file" => $file, "line" => $line, "offset" => $offset, "time" => 0.0];
		$this->start = hrtime(true);
	}

	/** NotORM debugTimer callback called after executing a query */
	function stop(): void {
		$this->entries[count($this->entries) - 1]["time"] = (hrtime(true) - $this->start) / 1e6;
	}

	/** Number of logged queries, use as $from in notice() */
	function count(): int {
		return count($this->entries);
	}

	/** The queries since $from are reported by the notice, e.g. the writes */
	function notice(int $from): void {
		for ($i = $from; $i < count($this->entries); $i++) {
			$this->notice[$i] = true;
		}
	}

	/** Mark the places in the page where the data of each query start, demo.js puts the query or a link to it there
	* @param string $html the page printed since bootstrap.inc.php
	* @param int $title offset of the title, the queries run before it are marked there
	* @param ?int $notice offset of the notice, the queries reported by it are marked there
	*/
	function mark(string $html, int $title, ?int $notice): string {
		$markers = [];
		foreach ($this->entries as $i => $entry) {
			$offset = $entry["offset"];
			if ($offset < $title) { // run before the page printed anything
				$offset = (isset($this->notice[$i]) && $notice !== null ? $notice : $title);
			}
			$open = strrpos(substr($html, 0, $offset), "<");
			if ($open !== false && $open > (int) strrpos(substr($html, 0, $offset), ">")) {
				$offset = $open; // not inside a tag
			}
			$markers[$offset] = ($markers[$offset] ?? "") . "<template data-query='" . ($i + 1) . "'></template>"; // <template> may be anywhere, even in <table> or <select>
		}
		krsort($markers);
		foreach ($markers as $offset => $marker) {
			$html = substr_replace($html, $marker, $offset, 0);
		}
		return $html;
	}

	/** Print the queries with the locations linked to the source */
	function render(): void {
		$www = dirname(__DIR__) . DIRECTORY_SEPARATOR . "www" . DIRECTORY_SEPARATOR;
		$language = sql_language($this->pdo);
		echo "<section class='log'>\n";
		foreach ($this->entries as $i => $entry) {
			$code = $this->statement($entry["file"], $entry["line"]);
			$file = str_replace("\\", "/", (str_starts_with($entry["file"], $www) ? substr($entry["file"], strlen($www)) : "../" . substr($entry["file"], strlen(dirname($www)) + 1)));
			echo "<div class='query' id='query-" . ($i + 1) . "'>";
			echo "<div class='meta'>#" . ($i + 1) . " ";
			echo a(strtr($this->source, ['$file' => $file, '$line' => $entry["line"]]), basename($file) . ":$entry[line]");
			echo " <span class='time'>" . format_time($entry["time"]) . "</span></div>\n";
			echo "<pre><code class='jush-php'>" . h($code) . "</code></pre>\n";
			echo "<pre><code class='jush-$language'>" . h($this->formatQuery($entry["query"], $entry["parameters"])) . "</code></pre>\n";
			echo "</div>\n";
		}
		echo "</section>\n";
	}

	/** File and line outside NotORM and include/ which caused the current query
	* @return array{string, int}
	*/
	private function caller(): array {
		$fallback = ["", 0];
		foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
			$file = $frame["file"] ?? "";
			if ($file == "" || str_starts_with($file, $this->skip[0])) {
				continue;
			}
			if (!str_starts_with($file, $this->skip[1])) {
				return [$file, $frame["line"] ?? 0];
			}
			if ($fallback[0] == "" && $file != __FILE__) {
				$fallback = [$file, $frame["line"] ?? 0];
			}
		}
		return $fallback;
	}

	/** PHP statement containing the line */
	private function statement(string $file, int $line): string {
		$this->statements[$file] ??= $this->statements($file);
		$found = [];
		foreach ($this->statements[$file] as [$first, $last, $code]) {
			if ($first <= $line && $line <= $last) {
				$found[] = $code;
			}
		}
		if (count($found) > 1) { // more statements on one line, prefer the ones working with objects
			$found = array_values(array_filter($found, fn($code) => str_contains($code, "->"))) ?: $found;
		}
		return ($found ? implode("\n", $found) : trim(file($file)[$line - 1] ?? ""));
	}

	/** Split a PHP file to statements at ; { } and PHP tags
	* @return list<array{int, int, string}> [first line, last line, code]
	*/
	private function statements(string $file): array {
		$return = [];
		$lines = file($file) ?: [];
		$code = "";
		$first = 0;
		$string = false; // inside "" or heredoc, where {$var} is not a block
		$parens = [0]; // open parentheses in each {} block, ; in for (;;) doesn't end the statement
		foreach (\PhpToken::tokenize(implode("", $lines)) as $token) {
			if ($token->text == '"' || $token->is([T_START_HEREDOC, T_END_HEREDOC])) {
				$string = !$string;
			}
			$end = !$string && (
				$token->is([T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG, T_INLINE_HTML, '{', '}'])
				|| ($token->text == ';' && end($parens) == 0)
			);
			if ($end) {
				if (!preg_match('~^[\s()\];,]*$~', $code)) {
					preg_match('~^[ \t]*~', $lines[$first - 1], $indent);
					$code = preg_replace(['~[ \t]+$~m', "~\n$indent[0]~"], ['', "\n"], rtrim($code));
					$return[] = [$first, $first + substr_count($code, "\n"), $code];
				}
				$code = "";
				if ($token->text == '{') {
					$parens[] = 0;
				} elseif ($token->text == '}' && count($parens) > 1) {
					array_pop($parens);
				}
				continue;
			}
			$text = ($token->is([T_COMMENT, T_DOC_COMMENT]) ? str_repeat("\n", substr_count($token->text, "\n")) : $token->text);
			if (!$string) {
				if ($token->text == '(' || $token->text == '[') {
					$parens[count($parens) - 1]++;
				} elseif ($token->text == ')' || $token->text == ']') {
					$parens[count($parens) - 1]--;
				}
			}
			if (trim($code) == "") {
				$first = $token->line + strspn($text, "\n");
				$code = ltrim($text);
			} else {
				$code .= $text;
			}
		}
		return $return;
	}

	/** Query with line breaks before the main clauses and the bound parameters in a comment
	* @param mixed[] $parameters
	*/
	private function formatQuery(string $query, array $parameters): string {
		$query = preg_replace_callback(
			'~\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*"|`[^`]*`|\s+(FROM|LEFT JOIN|WHERE|GROUP BY|HAVING|ORDER BY|LIMIT|UNION|VALUES|SET)\b~',
			fn($match) => (isset($match[1]) ? "\n$match[1]" : $match[0]),
			$query
		);
		if ($parameters) {
			$query .= "\n-- " . implode(", ", array_map(fn($val) => ($val === null ? "NULL" : (is_int($val) || is_float($val) ? (string) $val : $this->pdo->quote((string) $val))), $parameters));
		}
		return $query;
	}

}
