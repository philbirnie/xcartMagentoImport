<?php

namespace Importgen\Products;

use Composer\Config;
use \Importgen\DB;

class Configurable extends Simple
{

    public static $baseQuery = "
                    SELECT product_main.product_id,
                    product_main.name,
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

    public static $lightQuery = "
                SELECT product_main.product_id,
                    product_main.name,
                    product_main.visibility,
                    product_options.configurable_option,
                    product_options.option,
                    product_options.variant_sku,
                    product_options.sku,
                    product_options.variant_id,
                    product_options.inventory
                    FROM product_options
                    JOIN product_main ON product_options.product_id = product_main.product_id";

    public $simpleProducts = array();

    /*---------------------------------------------------------------
     * Static Methods
     *--------------------------------------------------------------*/

    /**
     * Extend createFromArray from parent function
     * @param $array
     * @return Simple
     */
    public static function createFromArray($array)
    {
        $product = parent::createFromArray($array);

        $configurable = new Configurable();

        foreach (get_object_vars($product) as $key => $value) {
            if($key != "type") {
                $configurable->$key = $value;
            }
        }

        $configurable->setIsInStock();
        $configurable->attributes = array();

        return $configurable;
    }


    public static function generateConfigurableFromId($id)
    {
        global $pdo;

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
                /**
                 * @var $configurable Configurable
                 */
                $configurable = self::createFromArray($row);
            }
            /**
             * @var $product \Importgen\Products\Simple
             */
            $product = Simple::createFromConfigurableArray($row);
            $configurable->addSimpleProduct($product);
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
        global $pdo;

        /**
         * @var Configurable
         */
        $configurable = new Configurable();


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
                $configurable = self::createFromArray($row);
            }
            /**
             * @var $product \Importgen\Products\Simple
             */
            $product = Simple::createFromConfigurableArray($row);
            $configurable->addSimpleProduct($product);
        }

        /**
         * Merge Simple Products that have duplicate skus
         */
        $configurable->mergeSimpleProducts();
        return $configurable;
    }

    public static function generateSiblingConfigurable($string)
    {
        /**
         * @var $pdo DB
         */
        global $pdo;

        /**
         * @var Configurable
         */
        $configurable = new Configurable();


        $query = self::$lightQuery . "
                    WHERE product_options.name LIKE :string_check";


        $stmt = $pdo->conn->prepare($query);
        $stmt->execute(array("string_check" => $string . "%"));

        $results = $stmt->fetchAll();

        $hasSiblings = $configurable->checkHasSiblings($results);

        if($hasSiblings) {

            /**
             * Create Base Configurable Product and Simple Products
             */
            foreach ($results as $row) {

                $row['weight'] = 0;
                $row['long_description'] = '';
                $row['short_description'] = '';
                $row['brand'] = '';
                $row['color'] = '';
                $row['msrp'] = 0;
                $row['price'] = 0;
                $row['free_tax'] = 'N';
                $row['type'] = 'Default';
                $row['gender'] = '';

                if (!$configurable->sku) {
                    $configurable = self::createFromArray($row);
                }
                /**
                 * @var $product \Importgen\Products\Simple
                 */
                $product = Simple::createFromConfigurableArray($row);
                $configurable->addSimpleProduct($product);
            }

            //Reconcile Sibling Products
            if ($hasSiblings) {
                $configurable->generateColorSpecProducts();
            }

            /**
             * Merge Simple Products that have duplicate skus
             */
            $configurable->mergeSimpleProducts();
        }
        return $configurable;
    }




    /*---------------------------------------------------------------
     * Constructor / Class Methods
     *--------------------------------------------------------------*/


    public function __construct()
    {
        $this->type = "configurable";
    }

    public function __destruct() {
        $this->simpleProducts = null;
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

    public function getMediaGalleryString() {
        $mediaGalleryString = '';

        /**
         * Append simple media galleries and base images
         * @var $simple Simple
         */
        foreach($this->simpleProducts as $simple) {
            if($simple->image) {
                array_push($this->media_gallery, $simple->image);
            }
            $this->media_gallery = array_merge($this->media_gallery, $simple->media_gallery);
        }

        $this->media_gallery = array_unique($this->media_gallery);


        foreach($this->media_gallery as $image) {
            $mediaGalleryString .= "/" . $image . ";";
        }
        /**
         * If media_gallery is not empty, remove trailing semicolon.
         */
        if (strlen($mediaGalleryString) > 0) {
            $mediaGalleryString = rtrim($mediaGalleryString, ";");
        }
        return $mediaGalleryString;
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
     * Reconcile Simple Products; loops through simples and conflates simple products with same variant_sku into one.
     */
    private function mergeSimpleProducts()
    {
        $temporarySimpleArray = array();

        /**
         * Loop through all simple products
         */
        foreach ($this->simpleProducts as $product) {
            /**
             * If product is already in the temporary array, just extract the attributes and add them to the array,
             * if not create a new simple product.
             */
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

    /**
     * Output as Array
     * Ref: config header row.
     */
    public function outputAsArray()
    {
        /**
         * @var array Parent Array
         */
        $base_array = parent::outputAsArray();

        /**
         * Set additional elements.
         */
        $base_array["configurable_attributes"] = $this->getConfigurableAttributes();
        $base_array["simple_skus"] = $this->getSimpleSkus();

        return $base_array;
    }

}
