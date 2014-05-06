<?php

/**
 * Database Class
 */
namespace Importgen\Products\Collection;

use Importgen\DB;
use Importgen\Products\Simple;

class SimpleCollection
{
    public $simpleProducts = array();

    public static $base_query = "
                    SELECT product_main.product_id,
                    product_main.name,
                    product_main.url_key,
                    product_main.free_tax,
                    product_main.brand,
                    product_main.msrp,
                    product_main.short_description,
                    product_main.long_description,
                    product_main.visibility,
                    product_main.weight,
                    product_main.price,
                    product_main.sku,
                    product_main.inventory,
                    product_extra_fields.type,
                    product_extra_fields.gender,
                    product_extra_fields.color
                    FROM product_main
                    JOIN product_extra_fields ON product_extra_fields.product_id = product_main.product_id
                    WHERE NOT EXISTS
                        (SELECT NULL FROM product_options
                        WHERE product_options.product_id = product_main.product_id
                        )
                    AND product_main.inventory > :inventory_min
                    AND product_main.visibility = :visibility_flag";


    //Build Collection
    public function __construct()
    {
    }

    public function __destruct() {
        $this->simpleProducts = null;
    }

    public function buildCollection()
    {
        $pdo = DB::get();
        $query = self::$base_query;


        $stmt = $pdo->conn->prepare($query);

        $stmt->execute(array("inventory_min" => 0, "visibility_flag" => 'Y'));

        /**
         * Build Simple Products
         */
        while ($row = $stmt->fetch()) {
            $simple = Simple::createFromArray($row);
            array_push($this->simpleProducts, $simple);
        }

        return $this;
    }
}
