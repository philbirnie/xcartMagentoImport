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
const DB_USER = "z";
const DB_PASSWORD = "z";
const DB_NAME = "city_gear_import";

/*---------------------------------------------------------------
 * Export Configuration/Varaibles
 *--------------------------------------------------------------*/

/**
 * Base Directory
 * @var $basedir string
 */
$basedir = __DIR__ . "/export";

/**
 * @var $filename string
 */
$filename = md5(time() . "product_export") . "_product_export.csv";

/**
 * @var $fullPath string
 */
$fullPath = $basedir . "/" . $filename;

/**
 * Header Columns
 * @var $header_row array
 */
$header_row = array("store","websites","sku","name","weight","special_price","price","categories","qty","is_in_stock","tax_class_id","attribute_set","type","configurable_attributes","visibility","media_gallery","image","small_image","thumbnail","short_description","description","gender","feature","brand","is_featured","shoe_size","color","color_spec","size","inseam","waist_size","hat_size","team","simple_skus","related_products");
