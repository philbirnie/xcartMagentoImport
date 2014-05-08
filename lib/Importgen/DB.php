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
    public static function get($alt_db = null)
    {
        $pdo = new DB($alt_db);
        return $pdo;
    }

    public function __construct($alt_db = null)
    {
        if(!is_null($alt_db)) {
            $db = $alt_db;
        } else {
            $db = DB_NAME;
        }
        try{
            /**
             * Create new PDO Object
             * @var PDO
             */
            $db_conn = new PDO('mysql:host='.DB_HOST.';dbname='.$db, DB_USER, DB_PASSWORD);
            $this->conn = $db_conn;
        } catch (PDOException $e) {
            echo "Unable to connect to database; please check your database settings "  . $e->getMessage();
        }
    }
}
