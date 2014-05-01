<?php

namespace Importgen\Products;

use \Importgen\DB;
use \Exception;

class Configurable extends Simple
{
    public $simpleProducts = array();

    public function __construct()
    {

    }

    public function reconcileSiblings($string)
    {
        $pdo = DB::get();

        echo "<pre>================= Reconciling Configurable Product: ${string} =================</pre>";

        $query = "SELECT product_main.name,
                    product_main.url_key,
                    product_options.variant_sku,
                    product_options.sku,
                    product_options.variant_id,
                    product_options.inventory,
                    product_main.free_tax,
                    product_main.brand,
                    product_options.weight,
                    product_options.price,
                    product_main.special_price,
                    product_main.short_description,
                    product_main.long_description,
                    product_options.configurable_option,
                    product_options.option
                    FROM product_options
                    JOIN product_main ON product_options.product_id = product_main.product_id
                    JOIN product_extra_fields ON product_extra_fields.product_id = product_options.product_id
                    WHERE product_options.name LIKE :string_check";

        $stmt = $pdo->conn->prepare($query);
        $stmt->execute(array("string_check" => $string . "%"));

        $hasSiblings = $this->checkHasSiblings($stmt);

        //Reconcile Sibling Products
        if ($hasSiblings) {
            echo("<pre>* This Product HAS SIBLINGS</pre>");

        } else {
            //All options are part of a single product
            echo "<pre>* This Product No Siblings</pre>";

            $configurableCreated = false;

            echo "<pre>* Generating Configurable Product<pre>";


            while($row = $stmt->fetch()) {

                $product = Simple::createFromArray($row);
                var_dump($row); exit();
                if(!$configurableCreated) {
                    $this->name = $row['name'];
                    $this->sku = $row['sku'];
                    $this->weight = $row['weight'];
                    $this->price = 
                    $configurableCreated = true;
                }
            }
        }
        return $this;
    }

    /**
     * Checks to see if we have siblings.
     * @param $execStmt \PDOStatement
     * @return bool
     */
    private function checkHasSiblings($execStmt)
    {
        if (!$execStmt) {
            throw new Exception("Query failed to Execute!");
        }
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
        while (($row = $execStmt->fetch()) && !$hasSiblings) {
            if (is_null($sku) || $row['sku'] === $sku) {
                $sku = $row['sku'];
            } else {
                //Set has siblings to true;
                //this will cause us to break out of loop.
                $hasSiblings = true;
            }
        }
        return $hasSiblings;
    }
}
