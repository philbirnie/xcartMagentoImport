<?php

namespace Importgen\Products;

use \Importgen\DB;
use \Exception;

class Configurable extends Simple
{

    public static $baseQuery = "
                    SELECT product_main.name,
                    product_main.url_key,
                    product_main.free_tax,
                    product_main.brand,
                    product_main.msrp,
                    product_main.short_description,
                    product_main.long_description,
                    product_main.visibility,
                    product_options.weight,
                    product_options.price,
                    product_options.configurable_option,
                    product_options.option,
                    product_options.variant_sku,
                    product_options.sku,
                    product_options.variant_id,
                    product_options.inventory,
                    product_extra_fields.type,
                    product_extra_fields.gender,
                    product_extra_fields.color
                    FROM product_options
                    JOIN product_main ON product_options.product_id = product_main.product_id
                    JOIN product_extra_fields ON product_extra_fields.product_id = product_options.product_id";

    public $simpleProducts = array();

    /*---------------------------------------------------------------
     * Static Methods
     *--------------------------------------------------------------*/

    public static function generateConfigurableFromId($id)
    {
        $pdo = DB::get();

        echo "<pre>================= Creating Configurable Product BY ID: ${id} =================</pre>";

        $configurable = new Configurable();

        $query = self::$baseQuery . "
                WHERE product_options.product_id = :id
        ";


        $stmt = $pdo->conn->prepare($query);
        $stmt->execute(array("id" => $id));
        $results = $stmt->fetchAll();


        /**
         * Create Base Configurable Product and Simple Products
         */
        foreach ($results as $row) {
            if (!$configurable->sku) {
                $configurable->populateFromArray($row);
                echo "<pre>* Configurable Product Created. (SKU: $configurable->sku)<pre>";
            }
            /**
             * @var $product \Importgen\Products\Simple
             */
            $product = Simple::createFromConfigurableArray($row);
            $configurable->addSimpleProduct($product);
            echo "<pre>*Child Product Added. (SKU: $product->sku)</pre>";
        }

        /**
         * Merge Simple Products that have duplicate skus
         */
        $configurable->mergeSimpleProducts();
        return $configurable;
    }

    public static function generateConfigurableFromString($string)
    {
        /**
         * @var $pdo DB
         */
        $pdo = DB::get();

        /**
         * @var Configurable
         */
        $configurable = new Configurable();

        echo "<pre>================= Creating Configurable Product BY STRING: ${string} =================</pre>";

        $query = self::$baseQuery . "
                    WHERE product_options.name LIKE :string_check";

        $stmt = $pdo->conn->prepare($query);
        $stmt->execute(array("string_check" => $string . "%"));

        $results = $stmt->fetchAll();

        /**
         * Create Base Configurable Product and Simple Products
         */
        foreach ($results as $row) {

            if (!$configurable->sku) {
                $configurable->populateFromArray($row);
                echo "<pre>* Configurable Product Created. (SKU: $configurable->sku)<pre>";
            }
            /**
             * @var $product \Importgen\Products\Simple
             */
            $product = Simple::createFromConfigurableArray($row);
            $configurable->addSimpleProduct($product);
            echo "<pre>*Child Product Added. (SKU: $product->sku)</pre>";
        }

        $hasSiblings = $configurable->checkHasSiblings($results);

        //Reconcile Sibling Products
        if ($hasSiblings) {
            echo("<pre>* This Product HAS SIBLINGS - Adding Color Specs to products.</pre>");
            $configurable->generateColorSpecProducts();
        } else {
            //All options are part of a single product
            echo "<pre>* This Product Has No Siblings</pre>";

            echo "<pre>* Generating Configurable Product<pre>";
        }

        /**
         * Merge Simple Products that have duplicate skus
         */
        $configurable->mergeSimpleProducts();
        return $configurable;
    }

    /*---------------------------------------------------------------
     * Constructor / Class Methods
     *--------------------------------------------------------------*/


    public function __construct()
    {
        $this->type = "configurable";
    }

    /**
     * Adds Simple Product to this configurable product
     * @param $simpleProduct \Importgen\Products\Simple
     * @return $this
     */
    public function addSimpleProduct($simpleProduct)
    {
        array_push($this->simpleProducts, $simpleProduct);
        return $this;
    }

    /**
     * Get Configurable Attributes from simple products
     * @return string Configurable attributes string ready for attribute
     */
    public function getConfigurableAttributes()
    {
        /**
         * @var $configurableAttributes string Holding Varaible for configurable attributes
         */
        $configurableAttributes = '';

        if (count($this->simpleProducts)) {
            /**
             * @var $simpleProduct Simple
             */
            $simpleProduct = array_pop($this->simpleProducts);

            /**
             * @var array
             */
            $keys = array_keys($simpleProduct->attributes);

            /**
             * @var $configurableAttributes string
             */
            $configurableAttributes = implode(",", $keys);
        }

        return $configurableAttributes;
    }

    /**
     * Checks to see if we have siblings.
     * @param $results array
     * @return bool
     */
    private function checkHasSiblings($results)
    {
        /**
         * Use SKU to check to see if we have more than one sku
         * If not, just create simple product;
         * if yes, reconcile products into one configurable.
         * @var $sku null|string
         */
        $sku = null;

        /**
         * Tracking Variable for hasSiblings
         * @var $hasSiblings boolean
         */
        $hasSiblings = false;

        /**
         * Loop through executedQuery to check if we have different skus among dataset.
         */
        foreach ($results as $row) {
            if (is_null($sku) || $row['sku'] === $sku) {
                $sku = $row['sku'];
            } else {
                //Set has siblings to true and break out of loop
                $hasSiblings = true;
                break;
            }
        }
        return $hasSiblings;
    }

    /**
     * Helper function to populate fields in Configurable product
     * @param $row array
     * @return $this
     */

    private function populateFromArray($row)
    {

        $tempProduct = self::createFromArray($row);

        foreach (get_object_vars($tempProduct) as $key => $value) {
            $this->$key = $value;
        }
        //Clear from Memory
        unset($tempProduct);

        //Clear out attributes for configurable product
        $this->attributes = array();

        return $this;
    }

    /**
     * Reconcile Simple Products; loops through simples and conflates simple products with same variant_sku into one.
     */
    private function mergeSimpleProducts()
    {
        $temporarySimpleArray = array();

        foreach ($this->simpleProducts as $product) {
            if (isset($temporarySimpleArray[$product->sku])) {
                foreach ($product->attributes as $attributeKey => $attributeValue) {
                    $temporarySimpleArray[$product->sku]->attributes[$attributeKey] = $attributeValue;
                }
            } else {
                $temporarySimpleArray[$product->sku] = $product;
            }
        }
        $this->simpleProducts = $temporarySimpleArray;
    }

    /**
     * Generate Color Spec products based on title string.
     */
    private function generateColorSpecProducts()
    {
        //Add color attribute based upon title;
        foreach ($this->simpleProducts as $product) {
            $color_position = strrpos($product->name, "-");
            if ($color_position !== false) {
                $color = trim(substr($product->name, $color_position + 1));
                $product->attributes['color_spec'] = $color;
            }
        }
    }

    /**
     * Get Simple Product skus
     * @return string List of simple product skus
     */
    public function getSimpleSkus()
    {
        $simpleSkus = array();
        foreach ($this->simpleProducts as $product) {
            array_push($simpleSkus, $product->sku);
        }
        return implode(",", $simpleSkus);
    }
}
