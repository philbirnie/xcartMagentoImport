<?php

namespace Importgen\Products;

class Simple
{
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
    public $categories;
    public $visibility;
    public $short_description;
    public $description;
    public $gender;
    public $brand;
    public $attributes = array();

    public static function createFromArray($array)
    {
        $simple = new Simple();

        $simple->name = $array['name'];
        $simple->sku = $array['sku'];
        $simple->weight = $array['weight'];
        $simple->visibility = 4;
        $simple->qty = $array['inventory'];
        $simple->description = $simple->stripEOLTags($array['long_description']);
        $simple->short_description = $simple->stripEOLTags($array['short_description']);
        $simple->brand = $array['brand'];
        $simple->color_spec = $array['color'];

        $simple->setUrlKey($array['url_key']);
        $simple->setPrice($array['msrp'], $array['price']);
        $simple->setIsInStock($array['visibility']);
        $simple->setTaxClassId($array['free_tax']);
        $simple->setAttributeSet($array['type']);;
        $simple->setGender($array['gender']);
        $simple->setCategories($array['type']);

        //Because color is actually in the extra_info table and could also be a config attribute, we need to add this to the
        //attributes differently
        if ($array['color']) {
            $simple->attributes['color_spec'] = $simple->convertLineBreakAttributes($array['color']);
        }

        if (isset($array['configurable_option'])) {
            $configurable_option = $simple->convertConfigurableOption($array['configurable_option']);
            $simple->addAttributeOption($configurable_option, $array['option']);
        }

        return $simple;
    }

    public static function createFromConfigurableArray($array)
    {
        /**
         * @var $simple Simple
         */
        $simple = self::createFromArray($array);
        /**
         * Modify SKU
         */
        $simple->sku = $simple->sku . "-" . $array["variant_id"];
        /**
         * Modify Visibility
         */
        $simple->visibility = 1;

        /**
         * Modify URL
         */
        $simple->url_key = $simple->url_key . "-" . $array["variant_id"];

        return $simple;
    }

    public function __construct()
    {
        $this->type = "simple";
    }

    /**
     * @param $configurable_option string Configurable option (e.g. waist_size)
     * @param $option string (e.g. XXL)
     */
    public function addAttributeOption($configurable_option, $option)
    {
        $this->attributes[$configurable_option] = $option;

    }


    /**
     * Sets Price and Special Price based upon msrp and price entered.
     * @param $msrp
     * @param $price
     */
    protected function setPrice($msrp, $price)
    {
        if ($msrp > $price) {
            $this->special_price = $price;
            $this->price = $msrp;
        } else {
            $this->price = $price;
        }
    }

    /**
     * Sets Appropriate In Stock Value
     * @param $value string Single character value of visibility
     */
    protected function setIsInStock($value)
    {
        $is_in_stock = 0;

        switch ($value) {
            case "Y":
                $is_in_stock = 1;
                break;
        }
        $this->is_in_stock = $is_in_stock;
    }

    /**
     * @param $value string Set Tax class id based upon free_tax value
     */
    protected function setTaxClassId($value)
    {
        switch ($value) {
            case "Y":
                $tax_class_id = 0;
                break;
            default:
                $tax_class_id = 2;
        }
        $this->tax_class_id = $tax_class_id;
    }

    protected function setColorSpec($value)
    {
        if ($value != '') {
            $this->color_spec = $value;
        }
    }

    /**
     * @param $value string Set Attribute Set
     */
    protected function setAttributeSet($value)
    {

        switch ($value) {
            case "Shorts":
            case "Pants":
                $attribute_set = "Pants";
                break;
            case "Shoes":
            case "Hats":
                $attribute_set = $value;
                break;
            default:
                $attribute_set = "Default";
        }

        $this->attribute_set = $attribute_set;
    }

    /**
     * @param $value string Set Base Category
     *
     * @return string Base Category String
     */
    protected function setCategories($value)
    {
        switch ($value) {
            case "Shoes":
                $category_string = "Shoes/Footwear";
                break;
            case "Accessory":
            case "Other":
                $category_string = "Accessories";
                break;
            case "Coats":
            case "Jackets":
            case "Outerwear":
                $category_string = "Apparel/Coats";
                break;
            case "Jeans":
            case "Pants":
            case "Shorts":
                $category_string = "Apparel/Jeans, Pants & Shorts";
                break;
            case "Shirts":
            case "Tee":
            case "Tees":
            case "Tee\\nShirts":
            case "Tanks":
                $category_string = "Apparel/Shirts, Tees & Tanks";
                break;
            case "Jewelry":
                $category_string = "Accessories/Jewelry";
                break;
            case "Watches":
                $category_string = "Accessories/Watches";
                break;
            case "Sweatshirts":
            case "Sweaters\\nSweatshirts":
            case "Sweaters":
                $category_string = "Apparel/Sweatshirts, Hoodies & Sweaters";
                break;
            case "Socks":
                $category_string = "Accessories/Socks";
                break;
            case "Hats":
                $category_string = "Accessories/Hats";
                break;
            default:
                $category_string = "";
        }


        $this->categories = $category_string;
    }

    /**
     * @param $value string Gender Passed string
     */
    protected function setGender($value)
    {
        $values = explode("\n", $value);

        foreach ($values as $key => $g) {
            switch ($g) {
                case "Men":
                case "Kids":
                case "Women":
                    $values[$key] = $value;
                    break;
                default:
                    $values[$key] = "";
            }
        }

        $this->gender = implode(",", $values);
    }

    protected function setUrlKey($value)
    {
        $this->url_key = strtolower(str_replace(" ", "-", $value));
    }

    protected function convertConfigurableOption($value)
    {
        switch ($value) {
            case "Size":
            case "Waist Size":
            case "Inseam":
            case "Hat Size":
            case "Shoe Size":
            case "Team":
                $option = strtolower(str_replace(" ", "_", $value));
                break;
            case "Waist":
                $option = "waist_size";
                break;
            case "Color":
                $option = "color_spec";
                break;
            case "Shoe Sizes":
                $option = "shoe_size";
                break;
            default:
                $option = "size";
        }
        return $option;
    }

    /**
     * Convenience function for retrieving attribute value
     * @param $attribute string Attribute Key
     * @return string
     */
    public function getAttributeValue($attribute)
    {
        $value = (isset($this->attributes[$attribute])) ? $this->attributes[$attribute] : '';

        return $value;
    }

    protected function stripEOLTags($string)
    {
        return str_replace("<EOL>", "", $string);
    }

    protected function convertLineBreakAttributes($value)
    {
        return str_replace("\\n", ",", $value);
    }

    /**
     * Output as Array
     * Ref: config header row.
     */
    public function outputAsArray()
    {
        return array(
            "store"         => "admin",
            "websites"      => "default",
            "sku"           => $this->sku,
            "name"          => $this->name,
            "weight"        => $this->weight,
            "special_price" => $this->special_price,
            "price"         => $this->price,
            "categories"    => $this->categories,
            "qty"           => $this->qty,
            "is_in_stock"   => $this->is_in_stock,
            "tax_class_id"  => $this->tax_class_id,
            "attribute_set" => $this->attribute_set,
            "type"          => $this->type,
            "configurable_attributes" => "", //Configurable Attributes
            "visibility"    => $this->visibility,
            "media_gallery" => "", //TODO: Media Gallery
            "image"         => "", //TODO: Image
            "small_image"   => "", //TODO: Small Image
            "thumbnail"     => "", //TODO: Thumbnail
            "short_description" => $this->short_description,
            "description"   => $this->description,
            "gender"        => $this->gender,
            "feature"       => "", //Feature
            "brand"         => $this->brand,
            "is_featured"   => "", //Is Featured
            "shoe_size"     => $this->getAttributeValue('shoe_size'),
            "color_family"  => "", //Color Family
            "color_spec"    => $this->getAttributeValue('color_spec'),
            "size"          => $this->getAttributeValue('size'),
            "inseam"        => $this->getAttributeValue('inseam'),
            "waist_size"    => $this->getAttributeValue('waist_size'),
            "hat_size"      => $this->getAttributeValue('hat_size'),
            "team"          => $this->getAttributeValue('team'),
            "simple_skus"   => "", //Simple SKUS
            "related_products" => "" //TODO: Related Products
        );
    }
}