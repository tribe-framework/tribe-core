<?php

namespace Wildfire\Core;

class MySQL {
	public $lastError; // Holds the last error
	public $lastQuery; // Holds the last query
	public $result; // Holds the MySQL query result
	public $records; // Holds the total number of records returned
	public $affected; // Holds the total number of records affected
	public $arrayedResult; // Holds an array of the result

	public $hostname; // MySQL Hostname
	public $username; // MySQL Username
	public $password; // MySQL Password
	public $database; // MySQL Database
	public $port; // MySQL Database

	public $databaseLink; // Database Connection Link

	public function __construct() {
		$this->Connect();
	}

	private function Connect() {
		$this->CloseConnection();

		$this->database = DB_NAME;
		$this->username = DB_USER;
		$this->password = DB_PASS;
		$this->hostname = DB_HOST;
		$this->port = DB_PORT;

		$this->databaseLink = mysqli_connect($this->hostname, $this->username, $this->password, $this->database, $this->port);
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
}
