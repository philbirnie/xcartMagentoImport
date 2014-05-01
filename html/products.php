<?php

require_once __DIR__.'/../vendor/autoload.php'; // load composer
require_once __DIR__.'/config.php'; //Local Autoloader

use CSanquer\ColibriCsv\CsvWriter;
use Importgen\DB;
use Importgen\Products\Collection\ConfigurableCollection;



/*---------------------------------------------------------------
 * Set up CSV File.
 *--------------------------------------------------------------*/

/**
 * @var $pdo Importgen\DB
 */
$pdo = DB::get();

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
 * @var $writer CSanquer\ColibriCsv\CsvWriter
 */
$writer = new CsvWriter(array(
   'delimiter' => ',',
   'enclosing_mode' => 'minimal',
    'trim' => true
));

/**
 * Header Columns
 * @var $header_row array
 */
$header_row = array("store","websites","sku","name","weight","special_price","price","categories","qty","is_in_stock","tax_class_id","attribute_set","type","configurable_attributes","visibility","media_gallery","image","small_image","thumbnail","short_description","description","gender","feature","brand","is_featured","shoe_size","color","color_spec","size","inseam","waist_size","hat_size","related_products");

/**
 * Configurable Products
 * @var $configurableCollection
 */
$configurableCollection = new ConfigurableCollection();
$configurableCollection->generateConfigurableProducts();


//$writer->open($fullPath);

/**
 * Write Header Row
 */
//$writer->writeRow($header_row);

//$writer->close();

?>





<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Export</title>
</head>
<body>
    <section id="main">
        <header>
            <h1>Export Complete</h1>
        </header>
        <p>Magento Product Import Located Here: <?php echo $fullPath; ?></p>
    </section>
</body>
</html>