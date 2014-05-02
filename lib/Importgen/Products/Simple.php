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
        $simple->setAttributeSet($array['type']);
        $simple->setGender($array['gender']);
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
        $tax_class_id = "Taxable Goods";

        switch ($value) {
            case "Y":
                $tax_class_id = "None";
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
        $this->url_key = strtolower(preg_replace("/ /", "-", $value));
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
                $option = strtolower(preg_replace("/ /", "_", $value));
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
        return preg_replace("/<EOL>/", "", $string);
    }

    protected function convertLineBreakAttributes($value)
    {
        return preg_replace("/\\\\n/", ",", $value);
    }

    /**
     * Output as Array
     * Ref: config header row.
     */
    public function outputAsArray()
    {
        return array(
            "admin",
            "default",
            $this->sku,
            $this->name,
            $this->weight,
            $this->special_price,
            $this->price,
            "", //TODO: Categories
            $this->qty,
            $this->is_in_stock,
            $this->tax_class_id,
            $this->attribute_set,
            $this->type,
            "", //Configurable Attributes
            $this->visibility,
            "", //TODO: Media Gallery
            "", //TODO: Image
            "", //TODO: Small Image
            "", //TODO: Thumbnail
            $this->short_description,
            $this->description,
            $this->gender,
            "", //Feature
            $this->brand,
            "", //Is Featured
            $this->getAttributeValue('shoe_size'),
            "", //Color Family
            $this->getAttributeValue('color_spec'),
            $this->getAttributeValue('size'),
            $this->getAttributeValue('inseam'),
            $this->getAttributeValue('waist_size'),
            $this->getAttributeValue('hat_size'),
            $this->getAttributeValue('team'),
            "", //Simple SKUS
            "" //TODO: Related Products
        );
    }
}