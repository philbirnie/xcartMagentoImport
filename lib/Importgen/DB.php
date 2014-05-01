<?php

/**
 * Database Class
 */
namespace Importgen;

use \PDOException as PDOException;
use \PDO;

class DB
{

    public $conn;



    /**
     * Convenience function for setting up database connection
     * @return DB Database connect or FALSE if could not connect
     */
    public static function get()
    {
        $pdo = new DB();
        return $pdo;
    }

    public function __construct()
    {
        try{
            /**
             * Create new PDO Object
             * @var PDO
             */
            $db_conn = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD);
            $this->conn = $db_conn;
        } catch (PDOException $e) {
            echo "Unable to connect to database; please check your database settings "  . $e->getMessage();
        }
    }
}
