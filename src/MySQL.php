<?php

namespace Wildfire\Core;

class MySQL {
	public $lastError; // Holds the last error
	public $lastQuery; // Holds the last query
	public $result; // Holds the MySQL query result
	public $records; // Holds the total number of records returned
	public $affected; // Holds the total number of records affected
	public $arrayedResult; // Holds an array of the result
	public $databaseLink; // Database Connection Link
	private $sqlQuery;
	private $schema;

	public function __construct() {
		$this->schema = ['id', 'content', 'updated_on', 'created_on', 'user_id', 'role_slug', 'slug', 'content_privacy', 'type'];

		$this->Connect();
	}

	private function Connect() {
		$this->CloseConnection();

		$database = isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : NULL;
		$username = isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : NULL;
		$password = isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : NULL;
		$hostname = isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost';
		$port = isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : 3306;

		$this->databaseLink = mysqli_connect($hostname, $username, $password, $database, (int) $port);
		if (!$this->databaseLink) {
			$this->lastError = "Error: Unable to connect to MySQL." . PHP_EOL;
			$this->lastError = "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
			$this->lastError = "Debugging error: " . mysqli_connect_error() . PHP_EOL;
			return false;
		}

		mysqli_set_charset($this->databaseLink, 'utf8');

		return true;
	}

	public function lastInsertID() {
		return mysqli_insert_id($this->databaseLink);
	}

	public function closeConnection() {
		if ($this->databaseLink) {
			mysqli_close($this->databaseLink);
		}
	}

	public function executeSQL($query) {
		$this->lastQuery = $query;
		if ($this->result = mysqli_query($this->databaseLink, $query)) {
			if (gettype($this->result) === 'object') {
				$this->records = @mysqli_num_rows($this->result);
				$this->affected = @mysqli_affected_rows($this->databaseLink);
			} else {
				$this->records = 0;
				$this->affected = 0;
			}

			if ($this->records > 0) {
				$this->arrayResults();
				if ($this->records == 1) {
					return array($this->unstrip_array($this->arrayedResult));
				} else {
					return $this->unstrip_array($this->arrayedResult);
				}
			} else {
				return 0;
			}
		} else {
			$this->lastError = mysqli_error($this->databaseLink);
			return false;
		}
	}

	public function unstrip_array($variable) {
		if (is_string($variable)) {
			if (json_decode($variable) === null) {
				return stripslashes($variable);
			} else {
				return $variable;
			}
		}
		if (is_array($variable)) {
			foreach ($variable as $i => $value) {
				$variable[$i] = $this->unstrip_array($value);
			}
		}

		return $variable;
	}

	public function arrayResults() {
		if ($this->records == 1) {
			return $this->arrayResult();
		}

		$this->arrayedResult = array();
		while ($data = mysqli_fetch_assoc($this->result)) {
			$this->arrayedResult[] = $data;
		}
		return $this->arrayedResult;
	}

	public function arrayResult() {
		$this->arrayedResult = mysqli_fetch_assoc($this->result) or die(mysqli_error($this->databaseLink));
		return $this->arrayedResult;
	}

	/**
	 * request column/keys from database
	 *
	 * @param string|null $column_keys comma separated list of keys or empty for all
	 */
	public function select($column_keys = null)
	{
		if (!$column_keys) {
			$select_columns = 'content';
		} else {
			$keys = \explode(',', $column_keys);
			$select_columns = '';

			foreach($keys as $i => $key) {
				$select_columns .= ($i != 0) ? ',' : ''; // add comma to separate fields

				$select_columns .= $this->queryWithSchema($key);
			}
		}

		$this->sqlQuery = "SELECT $select_columns FROM data";
		return $this;
	}

	/**
	 * WHERE condition joined by AND
	 *
	 * @param array $filter ['column', '=', 'pattern']
	 */
	public function andWhere(array $filter)
	{
		$this->sqlQuery = $this->whereClause($filter, 'and');
		return $this;
	}

	/**
	 * WHERE condition joined by OR
	 *
	 * @param array $filter ['column', '=', 'pattern']
	 */
	public function orWhere(array $filter)
	{
		$this->sqlQuery = $this->whereClause($filter, 'or');
		return $this;
	}

	/**
	 * WHERE NOT condition
	 *
	 * @param array $filter ['column', '=', 'pattern']
	 */
	public function notWhere(array $filter)
	{
		$this->sqlQuery = $this->whereClause($filter, 'not');
		return $this;
	}

	/**
	 * WHERE NOT condition joined by AND
	 *
	 * @param array $filter ['column', '=', 'pattern']
	 */
	public function andNotWhere(array $filter)
	{
		$this->sqlQuery = $this->whereClause($filter, 'andnot');
		return $this;
	}

	/**
	 * WHERE condition joined by OR
	 *
	 * @param array $filter ['column', '=', 'pattern']
	 */
	public function orNotWhere(array $filter)
	{
		$this->sqlQuery = $this->whereClause($filter, 'ornot');
		return $this;
	}

	/**
	 * set limit on the number of records fetched
	 *
	 * @param string|int $limit e.g-2 or '0,2'
	 */
	public function limit($limit)
	{
		$this->sqlQuery .= " LIMIT $limit";
		return $this;
	}

	/**
	 * ORDER BY on fetch request
	 *
	 * @param string $key
	 * @param string $order
	 */
	public function orderBy(string $key, $order = 'DESC')
	{
		$key = $this->queryWithSchema($key);
		$this->sqlQuery .= " ORDER BY $key $order";
		return $this;
	}

	/**
	 * GROUP BY on query
	 *
	 * @param string $key
	 */
	public function groupBy(string $key)
	{
		$qws_key = $this->queryWithSchema($key);
		$this->sqlQuery .= " GROUP BY $qws_key as '$key'";
		return $this;
	}

	/**
	 * run the query
	 */
	public function get()
	{
		$options = JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PARTIAL_OUTPUT_ON_ERROR;
		$q = \json_encode($this->executeSQL($this->sqlQuery), $options);

		$dash = new \Wildfire\Core\Dash;
		$q = $dash->jsonDecode($q);

		if (\sizeof($q) == 1) {
			$q = $q[0];
		}

		return $q;
	}

	/**
	 * Debug function: prints prepared query on screen
	 */
	public function query()
	{
		echo $this->sqlQuery;
		return $this;
	}

	private function whereClause(array $filter, string $condition): string
	{
		$query = $this->sqlQuery;

		if (!\strpos(strtolower($query), 'where')) {
			$query .= " WHERE ";

			if (\strtolower($condition) == 'not') {
				$query .= ' NOT ';
			}
		} else {
			switch(\strtolower($condition)) {
				case 'and':
					$query .= " AND ";
					break;

				case 'or':
					$query .= " OR ";
					break;

				case 'andnot':
					$query .= " AND NOT ";
					break;

				case 'ornot':
					$query .= " OR NOT ";
					break;

				default:
					break;
			}
		}

		$filter[0] = $this->queryWithSchema($filter[0]);
		$filter[2] = \is_numeric($filter[2]) ? (int) $filter[2] : "'$filter[2]'";

		$query .= "$filter[0] $filter[1] $filter[2]";
		return $query;
	}

	private function queryWithSchema($key)
	{
		if (\in_array($key, $this->schema)) {
			return "`$key`";
		} else {
			return "`content`->>'$.$key'";
		}
	}
}
