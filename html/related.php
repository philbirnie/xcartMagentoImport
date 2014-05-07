<?php

require_once __DIR__ . '/../vendor/autoload.php'; // load composer
require_once __DIR__ . '/config.php'; //Local Autoloader

use CSanquer\ColibriCsv\CsvWriter;
use Importgen\Products\Collection\RelatedCollection;

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Related Product Generation</title>
</head>
<body>
<h1>Related Product Generation</h1>


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
$writer->open($relatedFullPath);

/**
 * Write Header Row
 */
$writer->writeRow($related_header_row);

/*---------------------------------------------------------------
 * Create and output Related Products
 *--------------------------------------------------------------*/

/**
 * Configurable Products
 * @var $relatedCollection RelatedCollection
 */
$relatedCollection = new RelatedCollection();
$relatedCollection->cleanCollection();
$relatedCollection->buildCollection();

/**
 * @var $product Importgen\Products\Configurable
 */
foreach ($relatedCollection->productCollection as $product) {

    $row = array(
        "admin",
        "default",
        $product->sku,
        $product->related
    );

    /*
     * Write configurable to row.
     */
    $writer->writeRow($row);
}


$writer->close();

?>






<section id="main">
    <header>
        <h1>Export Complete</h1>
    </header>
    <p>Magento Product Import Located Here: <?php echo $relatedFullPath; ?></p>
</section>
</body>
</html>