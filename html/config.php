<?php


/*---------------------------------------------------------------
 * Register ImportGen Autoloader
 *--------------------------------------------------------------*/

function importgen_autoloader($class) {

    $base_dir = __DIR__.'/../lib/';
    $classPath = preg_replace("/\\\/","/", $class);


    include $base_dir . $classPath . '.php';
}

spl_autoload_register('importgen_autoloader');

/*---------------------------------------------------------------
 * Database Configuration
 *--------------------------------------------------------------*/

const DB_HOST = "127.0.0.1";
const DB_USER = "phil";
const DB_PASSWORD = "Floyd99";
const DB_NAME = "city_gear_import";
