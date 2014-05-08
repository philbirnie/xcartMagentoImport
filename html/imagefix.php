<?php

require_once('config.php');

use Importgen\DB;

$pdo = DB::get("city-gear");

/*---------------------------------------------------------------
 * Fixes Images links in Gallery
 *--------------------------------------------------------------*/

const COLOR_SPEC_ATTRIBUTE_ID = 177;


/*---------------------------------------------------------------
 * Get Products with Color Spec as a configurable attribute
 *--------------------------------------------------------------*/

$configurableProductIds = array();

$query = "SELECT product_id
          FROM catalog_product_super_attribute AS cpsa
          WHERE attribute_id = :attribute_id";

$stmt = $pdo->conn->prepare($query);
$stmt->execute(array("attribute_id" => COLOR_SPEC_ATTRIBUTE_ID));

$configurableProductIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

var_dump($configurableProductIds);

/*---------------------------------------------------------------
 * Get Child Products, Color Spec and Associated Images
 *--------------------------------------------------------------*/

foreach ($configurableProductIds as $configurableProductId) {
    $configurableImages = array();

    unset($stmt);

    $query = "SELECT cpei.value AS cspec, cpemg.value
            FROM catalog_product_relation AS cpe
            JOIN catalog_product_entity_int AS cpei ON cpe.child_id = cpei.entity_id
            JOIN catalog_product_entity_media_gallery AS cpemg ON cpemg.entity_id = cpe.child_id
            WHERE parent_id = :configurable_product_id AND cpei.attribute_id = :attribute_id
            GROUP BY cpemg.value";

    $stmt = $pdo->conn->prepare($query);
    $stmt->execute(array("configurable_product_id" => $configurableProductId, "attribute_id" => COLOR_SPEC_ATTRIBUTE_ID));

    while ($row = $stmt->fetch()) {
        $configurableImages[$row['cspec']][] = $row['value'];
    }


    /*---------------------------------------------------------------
     * If Configurable Images exist, update parent rows.
     *--------------------------------------------------------------*/

    foreach ($configurableImages as $spec => $images) {

        if (count($images)) {

            $imageString = implode('","', $images);
            $imageString = '"' . $imageString . '"';

            /**
             * Set Thumb Colors
             */
            $updateQuery = "UPDATE catalog_product_entity_media_gallery_value AS cpemgv
              JOIN catalog_product_entity_media_gallery AS cpemg
              ON cpemgv.value_id = cpemg.value_id
              SET thumb_color = :new_color
              WHERE cpemg.value IN ({$imageString}) AND cpemg.entity_id = :parent_sku";

            $stmt2 = $pdo->conn->prepare($updateQuery);
            $stmt2->execute(array("new_color" => $spec, "parent_sku" => $configurableProductId));
            echo "<pre>Result: " . $stmt2->rowCount() . " media images changed for $configurableProductId to $spec</pre>";

            unset($stmt2);
            /**
             * Set Swatch Color Linkage.
             */
            $image = array_shift($images);

            $updateQuery = "UPDATE catalog_product_entity_media_gallery_value AS cpemgv
              JOIN catalog_product_entity_media_gallery AS cpemg
              ON cpemgv.value_id = cpemg.value_id
              SET color = :new_color
              WHERE cpemg.value = :image AND cpemg.entity_id = :parent_sku";

            $stmt2 = $pdo->conn->prepare($updateQuery);
            $stmt2->execute(array("new_color" => $spec, "parent_sku" => $configurableProductId, "image" => $image));
            echo "<pre>Result: " . $stmt2->rowCount() . " media image swatch changed for $configurableProductId to $spec</pre>";
        }
    }
}


