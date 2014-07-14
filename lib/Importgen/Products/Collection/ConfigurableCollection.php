<?php

/**
 * Database Class
 */
namespace Importgen\Products\Collection;

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
        global $pdo;
        $query = "SELECT product_options.name, product_options.product_id
                    FROM product_options
                    JOIN product_main ON product_options.product_id = product_main.product_id
                    WHERE product_options.inventory > :inventory_min AND product_main.visibility = :visibility_flag
                    GROUP BY product_options.name
                    ORDER BY product_options.name";

        $stmt = $pdo->conn->prepare($query);
        $stmt->execute(array("inventory_min" => 0, "visibility_flag" => 'Y'));

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
        reset($this->configurableProductNames);
        /**
         * Loop through configurable products and determine new variants
         */
        while (list($key, $value) = each($this->configurableProductNames)) {
            //If we have a hyphen position, we have sibling variants that need to be reconciled.
            //Okay to use this because we can pretty safely assume that the hyphen will never be 0
            $configurableProduct = null;
            $configurableProduct = Configurable::generateConfigurableFromId($key);
            if (!is_null($configurableProduct)) {
                array_push($this->configurableProducts, $configurableProduct);
            }
        }
    }
}
