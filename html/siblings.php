<?php

require_once('config.php');

use Importgen\DB;

$pdo = DB::get("city-gear");

/*---------------------------------------------------------------
 * Sets up siblings.
 *--------------------------------------------------------------*/



/*---------------------------------------------------------------
 * Constants
 *--------------------------------------------------------------*/

const NAME_ATTRIBUTE = 71;

const LINK_TYPE_ID = 6;

const PRODUCT_LINK_ATTRIBUTE_ID = 6;


/*---------------------------------------------------------------
 * Initial Query to fetch all configurable products with hyphens
 *--------------------------------------------------------------*/

$query = 'SELECT cpe.entity_id, cpev.value
        FROM catalog_product_entity as cpe
        JOIN catalog_product_entity_varchar as cpev
        ON cpe.entity_id = cpev.entity_id
        WHERE type_id = "configurable"
          AND cpev.attribute_id = :attribute_id
          AND cpev.value
          LIKE "%-%"';

$stmt = $pdo->conn->prepare($query);
$stmt->execute(array("attribute_id" => NAME_ATTRIBUTE));

/**
 * Contains array of configurable products with names; key is entity_id
 */
$products = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

/**
 * Create a copy of products that can be used within foreach
 * @var $products_copy array
 */
$products_copy = $products;


/*---------------------------------------------------------------
 * Process Products
 *--------------------------------------------------------------*/

foreach ($products as $key => $product) {

    /**
     * Reset siblings array
     * @var $siblings array
     */
    $siblings = array();

    /**
     * Get base product name from last hyphen
     * We will use this to determine sibs.
     */
    $base_name = substr($product, 0, strrpos($product, "-"));

    /**
     * Filter through array, passing base_name
     */
    $siblings = array_filter($products_copy, function($value) use($base_name) {
        return strpos($value, $base_name) !== false;
    });

    /**
     * Remove current product from array (as it's not the sibling of itself
     */
    unset($siblings[$key]);
    var_dump($siblings);

    /**
     * Add Sibling
     */
    addSibling($key, $siblings);
}




/**
 * Adds sibling relationship to db.
 * @param $entity_id int
 * @param $siblings array
 */
function addSibling($entity_id, $siblings) {
    global $pdo;

    foreach($siblings as $key=>$value) {
        $query = "INSERT INTO catalog_product_link
              (product_id, linked_product_id, link_type_id)
              VALUES (?, ?, ?)";
        $stmt = $pdo->conn->prepare($query);

        $stmt->execute(array($entity_id, $key, LINK_TYPE_ID));

        $newID = $pdo->conn->lastInsertId();

        echo "<pre>Successfully inserted row ${newID}</pre>";

        if ($newID) {
            $attributeQuery = "INSERT INTO catalog_product_link_attribute_int
            (product_link_attribute_id, link_id, value)
            VALUES (?, ?, ?)";

            $attStmt = $pdo->conn->prepare($attributeQuery);

            $attStmt->execute(array(PRODUCT_LINK_ATTRIBUTE_ID, $newID, 0));

        }
    }
}


