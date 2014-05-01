<?php

namespace Importgen\Products;

class Simple {
    public $name;
    public $url_key;
    public $weight;
    public $sku;
    public $special_price;
    public $price;
    public $qty;
    public $is_in_stock;
    public $tax_class_id;
    public $attribute_set;
    public $type;
    public $visibility;
    public $short_description;
    public $description;
    public $gender;
    public $brand;
    public $attributes = array();

    public static function createFromArray($array) {
        $simple = new Simple();

        return $simple;
    }
}