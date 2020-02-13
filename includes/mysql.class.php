<?php
/*
 *  Copyright (C) 2012
 *     Ed Rackham (http://github.com/a1phanumeric/PHP-MySQL-Class)
 *  Changes to Version 0.8.1 copyright (C) 2013
 *	Christopher Harms (http://github.com/neurotroph)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// MySQL Class v0.8.1
class MySQL {
	
	// Base variables
    var $lastError;         // Holds the last error
	var $lastQuery;         // Holds the last query
	var $result;            // Holds the MySQL query result
	var $records;           // Holds the total number of records returned
	var $affected;          // Holds the total number of records affected
	var $rawResults;        // Holds raw 'arrayed' results
	var $arrayedResult;     // Holds an array of the result
	
	var $hostname;          // MySQL Hostname
	var $username;          // MySQL Username
	var $password;          // MySQL Password
	var $database;          // MySQL Database
	
	var $databaseLink;      // Database Connection Link

	/* *******************
	 * Class Constructor *
	 * *******************/
	
	function __construct($database, $username, $password, $hostname='localhost', $port=3306){
		$this->database = $database;
		$this->username = $username;
		$this->password = $password;
		$this->hostname = $hostname;
		
		$this->Connect();
	}
	
	
	
	/* *******************
	 * Private Functions *
	 * *******************/
	
	// Connects class to database
	// $persistant (boolean) - Use persistant connection?
	private function Connect($persistant = false){
		$this->CloseConnection();
		
		if($persistant){
			$this->databaseLink = mysqli_connect($this->hostname, $this->username, $this->password, $this->database);
		}else{
			$this->databaseLink = mysqli_connect($this->hostname, $this->username, $this->password, $this->database);
			if (!$this->databaseLink) {
				$this->lastError = "Error: Unable to connect to MySQL." . PHP_EOL;
				$this->lastError = "Debugging errno: " . mysqli_connect_errno() . PHP_EOL;
				$this->lastError = "Debugging error: " . mysqli_connect_error() . PHP_EOL;
				return false;
			}
		}

		mysqli_set_charset($this->databaseLink, 'utf8');
        
		return true;
	}
	
	
	// Performs a 'mysql_real_escape_string' on the entire array/string
	// Edited to 'addslashes' instead of depreciated 'mysql_real_escape_string' by Akshay Madan / Reflections Media
	private function SecureData($data, $types){
		
		if(is_array($data)){
            $i = 0;
			foreach($data as $key=>$val){
				if(!is_array($data[$key])){
                    $data[$key] = $this->CleanData($data[$key], $types[$i]);
					$data[$key] = mysqli_real_escape_string($this->databaseLink, $data[$key]);
                    $i++;
				}
			}
		}else{
            $data = $this->CleanData($data, $types);
			$data = mysqli_real_escape_string($this->databaseLink,$data);
		}
		
		return $data;
	}
    
    // clean the variable with given types
    // possible types: none, str, int, float, bool, datetime, ts2dt (given timestamp convert to mysql datetime)
    // bonus types: hexcolor, email
    private function CleanData($data, $type = ''){
			
        switch($type) {
            case 'none':
                $data = $data;
                break;
            case 'str':
                $data = settype( $data, 'string');
                break;
            case 'int':
                $data = settype( $data, 'integer');
                break;
            case 'float':
                $data = settype( $data, 'float');
                break;
            case 'bool':
                $data = settype( $data, 'boolean');
                break;
            // Y-m-d H:i:s
            // 2014-01-01 12:30:30
            case 'datetime':
                $data = trim( $data );
                $data = preg_replace('/[^\d\-: ]/i', '', $data);
                preg_match( '/^([\d]{4}-[\d]{2}-[\d]{2} [\d]{2}:[\d]{2}:[\d]{2})$/', $data, $matches );
                $data = $matches[1];
                break;
            case 'ts2dt':
                $data = settype( $data, 'integer');
                $data = date('Y-m-d H:i:s', $data);
                break;

            // bonus types
            case 'hexcolor':
                preg_match( '/(#[0-9abcdef]{6})/i', $data, $matches );
                $data = $matches[1];
                break;
            case 'email':
                $data = filter_var($data, FILTER_VALIDATE_EMAIL);
                break;
            default:
                $data = '';
                break;
        }
        return $data;
    }



    /* ******************
     * Public Functions *
     * ******************/

    // Executes MySQL query
    public function executeSQL ($query) {
        $this->lastQuery = $query;
        if($this->result = mysqli_query($this->databaseLink, $query)){
            if (gettype($this->result) === 'object') {
                $this->records  = @mysqli_num_rows($this->result);
                $this->affected = @mysqli_affected_rows($this->databaseLink);
            } else {
               $this->records  = 0;
               $this->affected = 0;
            }

            if($this->records > 0){
                $this->arrayResults();
				if ($this->records==1)
					return array($this->unstrip_array($this->arrayedResult));
				else
					return $this->unstrip_array($this->arrayedResult);
            }else{
                return 0;
            }

        }else{
            $this->lastError = mysqli_error($this->databaseLink);
            return false;
        }
    }

    public function columnsArray ($table) {
        return array_column($this->executeSQL("SHOW columns FROM `".$table."`"), 'Field');
    }

    // Adds a record to the database based on the array key names
    public function insert($table, $vars, $exclude = '', $datatypes){
		
        // Catch Exclusions
        if($exclude == ''){
            $exclude = array();
        }

        array_push($exclude, 'MAX_FILE_SIZE'); // Automatically exclude this one

        // Prepare Variables
        $vars = $this->SecureData($vars, $datatypes);
		
        $query = "INSERT INTO `{$table}` SET ";
        foreach($vars as $key=>$value){
            if(in_array($key, $exclude)){
                continue;
            }
            $query .= "`{$key}` = '{$value}', ";
        }

        $query = trim($query, ', ');
		
        return $this->executeSQL($query);
    }

    // Deletes a record from the database
    public function delete($table, $where='', $limit='', $like=false, $wheretypes){
        $query = "DELETE FROM `{$table}` WHERE ";
        if(is_array($where) && $where != ''){
            // Prepare Variables
            $where = $this->SecureData($where, $wheretypes);

            foreach($where as $key=>$value){
                if($like){
                    $query .= "`{$key}` LIKE '%{$value}%' AND ";
                }else{
                    $query .= "`{$key}` = '{$value}' AND ";
                }
            }

            $query = substr($query, 0, -5);
        }

        if($limit != ''){
            $query .= ' LIMIT ' . $limit;
        }

        return $this->executeSQL($query);
    }


    // Gets a single row from $from where $where is true
    public function select($from, $where='', $orderBy='', $limit='', $like=false, $operand='AND',$cols='*', $wheretypes){
        // Catch Exceptions
        if(trim($from) == ''){
            return false;
        }

        $query = "SELECT {$cols} FROM `{$from}` WHERE ";

        if(is_array($where) && $where != ''){
            // Prepare Variables
            $where = $this->SecureData($where, $wheretypes);

            foreach($where as $key=>$value){
                if($like){
                    $query .= "`{$key}` LIKE '%{$value}%' {$operand} ";
                }else{
                    $query .= "`{$key}` = '{$value}' {$operand} ";
                }
            }

            $query = substr($query, 0, -(strlen($operand)+2));

        }else{
            $query = substr($query, 0, -6);
        }

        if($orderBy != ''){
            $query .= ' ORDER BY ' . $orderBy;
        }

        if($limit != ''){
            $query .= ' LIMIT ' . $limit;
        }

        return $this->executeSQL($query);

    }

    // Updates a record in the database based on WHERE
    public function update($table, $set, $where, $exclude = '', $datatypes, $wheretypes){
        // Catch Exceptions
        if(trim($table) == '' || !is_array($set) || !is_array($where)){
            return false;
        }
        if($exclude == ''){
            $exclude = array();
        }

        array_push($exclude, 'MAX_FILE_SIZE'); // Automatically exclude this one

        $set 	= $this->SecureData($set, $datatypes);
        $where 	= $this->SecureData($where,$wheretypes);

        // SET

        $query = "UPDATE `{$table}` SET ";

        foreach($set as $key=>$value){
            if(in_array($key, $exclude)){
                continue;
            }
            $query .= "`{$key}` = '{$value}', ";
        }

        $query = substr($query, 0, -2);

        // WHERE

        $query .= ' WHERE ';

        foreach($where as $key=>$value){
            $query .= "`{$key}` = '{$value}' AND ";
        }

        $query = substr($query, 0, -5);

        return $this->executeSQL($query);
    }

    // 'Arrays' a single result
    public function arrayResult(){
        $this->arrayedResult = mysqli_fetch_assoc($this->result) or die (mysqli_error($this->databaseLink));
        return $this->arrayedResult;
    }

    // 'Arrays' multiple result
    public function arrayResults(){

        if($this->records == 1){
            return $this->arrayResult();
        }

        $this->arrayedResult = array();
        while ($data = mysqli_fetch_assoc($this->result)){
            $this->arrayedResult[] = $data;
        }
        return $this->arrayedResult;
    }

    // 'Arrays' multiple results with a key
    public function arrayResultsWithKey($key='id'){
        if(isset($this->arrayedResult)){
            unset($this->arrayedResult);
        }
        $this->arrayedResult = array();
        while($row = mysqli_fetch_assoc($this->result)){
            foreach($row as $theKey => $theValue){
                $this->arrayedResult[$row[$key]][$theKey] = $theValue;
            }
        }
        return $this->arrayedResult;
    }

    // Returns last insert ID
    public function lastInsertID(){
        return mysqli_insert_id($this->databaseLink);
    }

    // Return number of rows
    public function countRows($from, $where=''){
        $result = $this->select($from, $where, '', '', false, 'AND','count(*)');
        return $result["count(*)"];
    }

    // Closes the connections
    public function closeConnection(){
        if($this->databaseLink){
            mysqli_close($this->databaseLink);
        }
    }
	
	// Strip slashes off all MySQL results, by Akshay Madan / Reflections Media
	public function unstrip_array ($variable) {
		if (is_string($variable)) {
            if (json_decode($variable) === NULL)
                return stripslashes($variable);
            else
                return $variable;
        }
		if (is_array($variable))
			foreach($variable as $i=>$value)
				$variable[$i] = $this->unstrip_array($value) ;
		return $variable; 
	}
}