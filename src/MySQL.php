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
	public $schema;

    public function __construct()
    {
        $_schema = 'id,content,updated_on,created_on,user_id,role_slug,slug,content_privacy,type';
        $this->schema = explode(',', $_schema);

		$this->CloseConnection();

        $db_name = $_ENV['DB_NAME'] ?? null;
        $db_user = $_ENV['DB_USER'] ?? null;
        $db_pass = $_ENV['DB_PASS'] ?? null;
        $db_host = $_ENV['DB_HOST'] ?? 'localhost';
        $db_port = $_ENV['DB_PORT'] ?? 3306;

        $this->databaseLink = mysqli_connect($db_host, $db_user, $db_pass, $db_name, (int) $db_port);
        if (!$this->databaseLink) {
            $this->lastError = "Error: Unable to connect to MySQL." . PHP_EOL;
            $this->lastError = "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
            $this->lastError = "Debugging error: " . mysqli_connect_error() . PHP_EOL;

            throw new \Exception($this->lastError, 1);
            return false;
        }

        mysqli_set_charset($this->databaseLink, 'utf8mb4');

        return $this;
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

	public function unstrip_array($variable)
	{
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

	public function arrayResults()
	{
		if ($this->records == 1) {
			return $this->arrayResult();
		}

		$this->arrayedResult = array();
		while ($data = mysqli_fetch_assoc($this->result)) {
			$this->arrayedResult[] = $data;
		}
		return $this->arrayedResult;
	}

	public function arrayResult()
	{
		$this->arrayedResult = mysqli_fetch_assoc($this->result) or die(mysqli_error($this->databaseLink));
		return $this->arrayedResult;
	}
}
