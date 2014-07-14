<?php

require_once __DIR__ . '/../vendor/autoload.php'; // load composer
require_once __DIR__ . '/config.php'; //Local Autoloader

use CSanquer\ColibriCsv\CsvWriter;
use Importgen\Products\Collection\ConfigurableCollection;
use Importgen\Products\Collection\SimpleCollection;

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Base Product Generation</title>
</head>
<body>
<h1>Base Product Generation</h1>


<?php



/*---------------------------------------------------------------
 * Set up CSV File.
 *--------------------------------------------------------------*/

/**
 * @var $writer CSanquer\ColibriCsv\CsvWriter
 */
$writer = new CsvWriter(array(
    'delimiter' => ',',
    'enclosing_mode' => 'minimal',
    'trim' => true
));
$writer->open($fullPath);

/**
 * Write Header Row
 */
$writer->writeRow($product_header_row);

/*---------------------------------------------------------------
 * Create and output Simple Products
 *--------------------------------------------------------------*/

/**
 * @var $simpleCollection SimpleCollection
 */
$simpleCollection = new SimpleCollection();
$simpleCollection->buildCollection();


die(var_dump($simpleCollection->simpleProducts));

/**
 * @var $product \Importgen\Products\Simple
 */
foreach($simpleCollection->simpleProducts as $product) {
    $writer->writeRow($product->outputAsArray());
}

/**
 * Free Simple Collection from memory.
 */
$simpleCollection = null;
unset($simpleCollection);

/*---------------------------------------------------------------
 * Create and output Configurable Products
 *--------------------------------------------------------------*/

/**
 * Configurable Products
 * @var $configurableCollection
 */
$configurableCollection = new ConfigurableCollection();
$configurableCollection->generateConfigurableProducts();

/**
 * @var $product Importgen\Products\Configurable
 */
foreach ($configurableCollection->configurableProducts as $product) {
    /**
     * Write Simple Products to spreadsheet
     * @var $simple Importgen\Products\Simple
     */
    foreach ($product->simpleProducts as $simple) {
        $writer->writeRow($simple->outputAsArray());
    }
    /**
     * Write configurable to row.
     */
    $writer->writeRow($product->outputAsArray());
}


$writer->close();

?>






<section id="main">
    <header>
        <h1>Export Complete</h1>
    </header>
    <p>Magento Product Import Located Here: <?php echo $fullPath; ?></p>
</section>
</body>
</html>