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
	public $schema;

    public function __construct()
    {
        $this->schema = ['id', 'content', 'updated_on', 'created_on', 'user_id', 'role_slug', 'slug', 'content_privacy', 'type'];

        $this->Connect();
    }

    private function Connect()
    {
        $this->CloseConnection();

        $database = isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : null;
        $username = isset($_ENV['DB_USER']) ? $_ENV['DB_USER'] : null;
        $password = isset($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : null;
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

    public function lastInsertID()
    {
        return mysqli_insert_id($this->databaseLink);
    }

    public function closeConnection()
    {
        if ($this->databaseLink) {
            mysqli_close($this->databaseLink);
        }
    }

    public function executeSQL($query)
    {
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
	 * flattens database query result and organizes it (also respects privacy)
	 *
	 * @param array $queryResponse db query result array
	 * @param boolean $respect_privacy default:true
	 * @param boolean $do_expand_recurrsive
	 * @return array|null array, or null if validation fails
	 */
    public function cleanUpQueryResponse(array $queryResponse, bool $respect_privacy = true, bool $do_expand_recurrsive = false)
    {
		foreach($queryResponse as $key => $value) {
			try {
				if (\gettype($value) != 'array') {
					if (!$value) {
						$finalResponse[$key] = $value;
						continue;
					}

					if ($key == 'content') {
						$finalResponse = $do_expand_recurrsive ? $this->jsonDecode($value) : \json_decode($value, 1);
					} else {
						$finalResponse[$key] = $this->jsonDecode($value);
					}
				} else {
					$finalResponse = $this->cleanUpQueryResponse($value, $respect_privacy, $do_expand_recurrsive);
				}
			} catch (\TypeError $e) {
				$finalResponse = $value;
				continue;
			}
		}

        if (!$respect_privacy) {
            return $finalResponse;
        }

		$auth = new \Wildfire\Auth\Auth;
		$currentUser = $auth->getCurrentUser() ?? ['user_id' => null];

		switch ($finalResponse['content_privacy']) {
			case 'draft':
				if ($currentUser['user_id'] != $finalResponse['user_id']) {
					$finalResponse = null;
				}
				break;
			case 'pending':
				if (
					$currentUser['role'] != 'admin' ||
					$currentUser['user_id'] != $finalResponse['user_id'] ||
					!($_ENV['SKIP_CONTENT_PRIVACY'] ?? false)
				) {
					$finalResponse = null;
				}
				break;
		}

        return $finalResponse;
    }

	/**
	 * request column/keys from database
	 *
	 * @param string|null $column_keys comma separated list of keys or empty for all
	 */
	public function select($column_keys = null)
	{
		if (!$column_keys) {
			$select_columns = '*';
		} else {
			$keys = \explode(',', $column_keys);
			$select_columns = '';

            foreach ($keys as $i => $key) {
                $select_columns .= ($i != 0) ? ',' : ''; // add comma to separate fields

                $select_columns .= $this->validateKeyWithSchema($key, true);
            }
        }

        $this->sqlQuery = "SELECT $select_columns FROM data";
        return $this;
    }

	/**
	 * accepts a set of identifiers, prepares and runs sql query and returns response
	 *
	 * @param array|string|int $identifier can be [[type=>$type, slug=>$slug], ...], [type=>$type,slug=>$slug], 'id1,id2,id3', or [[id=>$id],...]
	 */
	public function getRows ($identifier)
	{
		$sqlQuery = "$this->sqlQuery WHERE";

		// if $identifier is a csv of ids
		if (is_string($identifier) && strpos($identifier, ',')) {
			$_ids = \explode(',', $identifier);
			$_ids = array_map('trim', $_ids);
		}

		if (isset($identifier['type'])) {
			/**
			 * interface to handle $identifier['type' && 'slug']
			 */
			$_where = "`slug` = '{$identifier['slug']}' AND `type` = '{$identifier['type']}'";
			// returns single row matching type and slug
			$sqlQuery .= "$_where ORDER BY id DESC LIMIT 0,1";
		} else if ((\is_array($identifier) && !isset($identifier[0]['type'])) || isset($_ids)) {
			/**
			 * interface to handle $q[*]['id] from get_all_ids
			 * or a csv containing ids
			 */

			// extracting ids and preparing them for sql "where in"
			if (!isset($_ids)) {
				$_ids = \array_column($identifier, 'id');
			}

			$_ids = json_encode($_ids);
			$_ids = \str_replace('[', '(', $_ids);
			$_ids = \str_replace(']', ')', $_ids);

			// returns multiple rows
			$sqlQuery .= "`id` IN $_ids ORDER BY `id` DESC";
		} else if (\is_array($identifier) && isset($identifier[0]['type'])) {
			// extract type & slug columns
			$_slugs = \array_column($identifier, 'slug');
			$_types = \array_column($identifier, 'type');

			// convert values to string
			$_slugs = json_encode($_slugs);
			$_types = json_encode($_types);

			// replace '[]' with '()'
			$_slugs = \str_replace('[', '(', $_slugs);
			$_slugs = \str_replace(']', ')', $_slugs);
			$_types = \str_replace('[', '(', $_types);
			$_types = \str_replace(']', ')', $_types);

			// returns multiple rows
			$sqlQuery .= "`slug` IN $_slugs AND `type` IN $_types ORDER BY `id` DESC";
		} else if (is_numeric($identifier)) {
			// return single row matching id
			$sqlQuery .= "`id`='$identifier' ORDER BY `id` DESC LIMIT 0,1";
		}

		$this->sqlQuery = $sqlQuery;
		$sql_rows = $this->executeSQL($sqlQuery);

		return $sql_rows;
	}

    public function count()
    {
        $this->sqlQuery = "SELECT count(*) AS 'count' FROM data";
        return $this;
    }

    /**
    * Where condition
    *
    * @param string $filter  space separated string: "type = user"
    * @return void
    */
    public function where(string $filter)
    {
        $this->sqlQuery = $this->whereClause($filter);
        return $this;
    }

    /**
    * WHERE condition joined by AND
    *
    * @param string $filter  space separated string: "type = user"
    */
    public function andWhere(string $filter)
    {
        $this->sqlQuery = $this->whereClause($filter, 'and');
        return $this;
    }

    /**
    * WHERE condition joined by OR
    *
    * @param string $filter  space separated string: "type = user"
    */
    public function orWhere(string $filter)
    {
        $this->sqlQuery = $this->whereClause($filter, 'or');
        return $this;
    }

    /**
    * WHERE NOT condition
    *
    * @param string $filter  space separated string: "type = user"
    */
    public function notWhere(string $filter)
    {
        $this->sqlQuery = $this->whereClause($filter, 'not');
        return $this;
    }

    /**
    * WHERE NOT condition joined by AND
    *
    * @param string $filter  space separated string: "type = user"
    */
    public function andNotWhere(string $filter)
    {
        $this->sqlQuery = $this->whereClause($filter, 'andnot');
        return $this;
    }

    /**
    * WHERE condition joined by OR
    *
    * @param string $filter  space separated string: "type = user"
    */
    public function orNotWhere(string $filter)
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
        $key = $this->validateKeyWithSchema($key);
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
        $qws_key = $this->validateKeyWithSchema($key);
        $this->sqlQuery .= " GROUP BY $qws_key as '$key'";
        return $this;
    }

	/**
	 * Fetch db record based on id or run the query
	 *
	 * @param  int $id id of record in database
	 * @param  bool $respect_privacy    default:true
	 * @return array|null	array or null if nothing is found
	 */
	public function get(int $id = null, bool $respect_privacy = true)
	{
		if (!$id) {
			$q = $this->executeSQL($this->sqlQuery);

			if ($q && \sizeof($q) > 0) {
				foreach($q as $r) {
					$tmp = $this->cleanUpQueryResponse($r, $respect_privacy);
					if ($tmp) {
						$queryResponse[] = $tmp;
					}
				}
			}

			return $queryResponse;
		}

		try {
            $q = $this->executeSQL("SELECT * FROM data WHERE id = '{$id}' limit 1");

			if ($q[0]['content']) {
				return $this->cleanUpQueryResponse($q[0], $respect_privacy);
			}
        } catch (\Error $e) {
            return 0;
        }
	}

	/**
	 * Debug function: prints prepared query on screen
	 */
	public function print()
	{
		echo $this->sqlQuery;
		return $this;
	}

    private function whereClause(string $filter, string $condition = ''): string
    {
        $query = $this->sqlQuery;

        if (!\strpos(strtolower($query), 'where')) {
            $query .= " WHERE ";

            if (\strtolower($condition) == 'not') {
                $query .= ' NOT ';
            }
        } else {
            switch (\strtolower($condition)) {
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

		$filter = \explode(' ', $filter);
		$filter[0] = $this->validateKeyWithSchema($filter[0]);
		$filter[2] = \is_numeric($filter[2]) ? (int) $filter[2] : "'$filter[2]'";

		$query .= "$filter[0] $filter[1] $filter[2]";
		return $query;
	}

	/**
	 * validates key/column names with db schema and prepends `content` if required
	 *
	 * @param string $key
	 * @return string
	 */
	public function validateKeyWithSchema(string $key): string
	{
		if (\in_array($key, $this->schema)) {
			return "`$key`";
		} else {
			return "`content`->>'$.$key' AS '$key'";
		}
	}

	/**
	 * takes a json string and returns deeply nested array decoded
	 *
	 * @param string $data
	 * @return void
	 */
	public function jsonDecode($data)
	{
		if (\is_string($data)) {
			$decoded_data =  \json_decode($data, 1);
		}

		if (!$decoded_data) {
			return $data;
		}

		if (!\is_array($decoded_data)) {
			return $decoded_data;
		}

		foreach ($decoded_data as $key => $value) {
			if (\is_string($value)) {
				$decoded_data[$key] = $this->jsonDecode($value);
			} else if (\is_array($value)) {
				foreach ($value as $value_key => $value_value) {
					$value[$value_key] = $this->jsonDecode($value_value);
				}

				$decoded_data[$key] = $value;
			}
		}

		return $decoded_data;
	}
}
