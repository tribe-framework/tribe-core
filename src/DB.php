<?php


namespace Wildfire\Core;

/**
 * Class DB provides methods for interfacing with PHP Data Objects (PDO)
 * @package Wildfire\Core
 * @author Apurv Jyotirmay
 * @version 1.0
 */
class DB
{
    // properties
    private string $dbHost;
    private string $dbName;
    private string $dbUser;
    private string $dbPass;
    private $dbh;  // database handle
    private $psh = null; // prepared statement handle
    public $lastReasult = null;

    public function __construct() {
        $this->dbHost = $_ENV['DB_HOST'];
        $this->dbName = $_ENV['DB_NAME'];
        $this->dbUser = $_ENV['DB_USER'];
        $this->dbPass = $_ENV['DB_PASS'];

        $this->initialiseDatabaseConnection();
    }

    private function initialiseDatabaseConnection() {
        // create new database connection
        try {
            $this->dbh = null;

            /**
             * Setup connection to database
             * This is usually called database handle (dbh)
             */
            $dbh = new \PDO("mysql:host=$this->dbHost;dbname=$this->dbName", $this->dbUser, $this->dbPass);

            /**
             * Use PDO::ERRMODE_EXCEPTION, to capture errors and write them to a log
             * file for later inspection instead of printing them on a screen
             */
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // set global $dbh
            $this->dbh = $dbh;

            unset($dbh);
        } catch (\PDOException $e) {
            /**
             * log PDO exceptions to PHP's system logger, using the log engine
             * of operating system
             *
             * For more logging options visit
             * http://php.net/manual/en/function.error-log.php
             */
            error_log('PDOException - '.$e->getMessage(), 0);

            /**
             * Stop executing, return an Internal Server Error HTTP Status (500)
             * and display an error
             */
            http_response_code(500);

            $e500File = THEME.'/errors/500.php';

            if (file_exists($e500File)) {
                include_once $e500File;
                die();
            } else {
                die('Internal Server Error');
            }
        }
    }

    /**
     * Creates prepared statements from SQL Queries
     *
     * @access public
     * @param string $sql_query
     * @example "SELECT username FROM users WHERE id = :id"
     */
    public function prepare(string $sql_query) {
        $this->psh = $this->dbh->prepare($sql_query);
        return $this;
    }

    /**
     * Binds parameters to prepared statement
     *
     * @access public
     * @param array $params
     * @example Array([':id', $id])
     */
    public function bind (array $params = null) {
        if (!$params) {
            \error_log('Wildfire\Core\DB->bind($params): $params cannot be null');
            return false;
        }

        if (!is_array($params)) {
            \error_log('Wildfire\Core\DB->bind($params): $params is not an array');
            return false;
        }

        try {
            foreach ($params as $key => &$val) {
                if (\gettype($val) === 'integer') {
                    // setting PDO::PARAM_INT otherwise it would default to char
                    $this->psh->bindParam($key, $val, \PDO::PARAM_INT);
                } else {
                    $this->psh->bindParam($key, $val);
                }
            }
        } catch (\PDOException $e) {
            error_log('Wildfire\Core\DB->bind(): '.$e->getMessage(), 0);
        }

        return $this;
    }

    /**
     * Executes prepared statement and returns results
     *
     * @access public
     */
    public function exec() {
        $psh = $this->psh;

        if (!$psh) {
            error_log('SQL query not prepared');
            return false;
        }

        try {
            $psh->execute();
            $this->lastReasult = $psh->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log('Wildfire\Core\DB->exec(): '.$e->getMessage(), 0);
        }

        return $psh->rowCount() === 1 ? $this->lastReasult[0] : $this->lastReasult;
    }
}