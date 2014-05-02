<?php

require_once __DIR__.'/../vendor/autoload.php'; // load composer
require_once __DIR__.'/config.php'; //Local Autoloader

use CSanquer\ColibriCsv\CsvWriter;
use Importgen\Products\Collection\ConfigurableCollection;
use Importgen\Products\Collection\SimpleCollection;



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
$writer->writeRow($header_row);


/**
 * Configurable Products
 * @var $configurableCollection
 */
//$configurableCollection = new ConfigurableCollection();
//$configurableCollection->generateConfigurableProducts();

$simpleCollection = new SimpleCollection();
$simpleCollection->buildCollection();


/**
 * @var $product \Importgen\Products\Simple
 */
foreach($simpleCollection->simpleProducts as $product) {
    $writer->writeRow($product->outputAsArray());
}

$writer->close();

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