<?php

/**
 * Database Class
 */
namespace Importgen\Products\Collection;

use Importgen\DB;
use Importgen\Products\Configurable;

class ConfigurableCollection
{
    public $configurableProductNames = array();
    public $configurableProducts = array();

    //Build Collection
    public function __construct()
    {
        $this->setProductNames();
    }

    private function setProductNames()
    {
        $pdo = DB::get();
        $query = "SELECT name, product_id
                    FROM product_options
                    WHERE inventory > :inventory_min
                    GROUP BY name
                    ORDER BY name";

        $stmt = $pdo->conn->prepare($query);
        $stmt->execute(array("inventory_min" => 0));

        /**
         * Add product names to array;
         */
        while ($row = $stmt->fetch()) {
            $this->configurableProductNames[$row['product_id']] = $row['name'];
        }

        return $this;
    }

    public function generateConfigurableProducts()
    {
        /**
         * Loop through configurable products and determine new variants
         */
        while (list($key, $value) = each($this->configurableProductNames)) {
            //If we have a hyphen position, we have sibling variants that need to be reconciled.
            //Okay to use this because we can pretty safely assume that the hyphen will never be 0
            $configurableProduct = null;
            if ($hyphenPosition = strrpos($value, "-")) {
                $searchString = substr($value, 0, $hyphenPosition);
                $configurableProduct = Configurable::generateConfigurableFromString($searchString);
            } else {
                echo $value;
                //We'll assume that this is its own product with no siblings.
                $configurableProduct = Configurable::generateConfigurableFromId($key);
            }
            if (!is_null($configurableProduct)) {
                array_push($this->configurableProducts, $configurableProduct);
            }
        }
    }
}
