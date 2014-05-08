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

        //Consolidate configurableProductNames
        $this->consolidateConfigurableProductNames();

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
            if ($hyphenPosition = strrpos($value, "-")) {
                $searchString = substr($value, 0, $hyphenPosition);
                $configurableProduct = Configurable::generateConfigurableFromString($searchString);
                //var_dump($configurableProduct);
            } else {
                //We'll assume that this is its own product with no siblings.
                $configurableProduct = Configurable::generateConfigurableFromId($key);
                //var_dump($configurableProduct);
                //die();
            }
            if (!is_null($configurableProduct)) {
                array_push($this->configurableProducts, $configurableProduct);
            }
        }
    }

    /**
     * Remove sibling duplicates.
     */
    private function consolidateConfigurableProductNames()
    {
        reset($this->configurableProductNames);
        /**
         * @var $configurableProductNames array Copy of configurableProductNames
         */
        $configurableProductNames = $this->configurableProductNames;

        /**
         * Loop through configurable product names
         */
        while(list($product_id, $name) = each($this->configurableProductNames)) {
            /**
             * Get string from last hyphen and check to see if we have other products
             * that match; if so, get rid of them as they will be automatically captured
             * in the siblings.
             */
            if($hyphenPosition = strrpos($name, "-")) {
                $searchString = substr($name, 0, $hyphenPosition);
                $matches = array();
                foreach($configurableProductNames as $product_id => $value) {

                    if(strpos($value, $searchString) !== false) {
                        array_push($matches, $product_id);
                    }
                }
                if(count($matches) > 1) {
                    array_shift($matches);
                    foreach($matches as $key) {
                        unset($this->configurableProductNames[$key]);
                    }
                }
            }
        }
    }

}
