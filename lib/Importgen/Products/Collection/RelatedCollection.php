<?php


namespace Importgen\Products\Collection;

use Importgen\Products\Simple;
use Importgen\Products\Configurable;


class RelatedCollection
{

    public $productCollection = array();

    //Build Collection
    public function __construct()
    {
    }

    public function buildCollection()
    {
        global $pdo;

        $query = "SELECT sku, GROUP_CONCAT(product_sku_to SEPARATOR ',') AS related
                  FROM related_products
                  GROUP BY sku";

        $stmt = $pdo->conn->prepare($query);
        $stmt->execute();

        while ($row = $stmt->fetch()) {
            $simple = new Simple();
            $simple->sku = $row['sku'];
            $simple->related = $row['related'];
            array_push($this->productCollection, $simple);
        }
    }

    /**
     * Cleans related collection by updating skus for sibling products that have been consolidated
     */
    public function cleanCollection()
    {
        $changedSkus = array();

        /**
         * @var $configurableCollection ConfigurableCollection
         */
        $configurableCollection = new ConfigurableCollection();

        /**
         * Loop through configurable products and determine new variants
         */
        while (list($key, $value) = each($configurableCollection->configurableProductNames)) {
            //If we have a hyphen position, we have sibling variants that need to be reconciled.
            //Okay to use this because we can pretty safely assume that the hyphen will never be 0
            $configurableProduct = null;
            if ($hyphenPosition = strrpos($value, "-")) {
                $searchString = substr($value, 0, $hyphenPosition);
                $configurableProduct = Configurable::generateSiblingConfigurable($searchString);
                if ($configurableProduct->sku) {
                    $newSku = $configurableProduct->sku;
                    /**
                     * @var $simple Simple
                     */
                    foreach ($configurableProduct->simpleProducts as $simple) {
                        $legacySku = $simple->getLegacySku();
                        $changedSkus[$newSku][] = $legacySku;
                    }
                }
            }
        }

        foreach ($changedSkus as $key => $changed) {
            global $pdo;

            $changed = array_unique($changed);

            $changed_list = implode('","', $changed);
            $changed_list = '"' . $changed_list . '"';

            $query = "
                    UPDATE related_products
                    SET sku = :new_sku
                    WHERE sku IN ({$changed_list})";


            $stmt = $pdo->conn->prepare($query);

            $result = $stmt->execute(array("new_sku" => $key));

            if($result) {
                echo "<pre>Sku Column: " . $stmt->rowCount() . " skus changed to $key</pre>";
            } else {
                $error = $stmt->errorInfo();
                echo "Query failed with message: " . $error[2];
            }

            unset($stmt);

            $query = "
                    UPDATE related_products
                    SET product_sku_to = :new_sku
                    WHERE product_sku_to IN ({$changed_list})";
            $stmt = $pdo->conn->prepare($query);
            $stmt->execute(array("new_sku" => $key));

            if($result) {
                 echo "<pre>Product to Sku Column: " . $stmt->rowCount() . " skus changed to $key</pre>";
            } else {
                $error = $stmt->errorInfo();
                echo "Query failed with message: " . $error[2];
            }


        }
    }

}
