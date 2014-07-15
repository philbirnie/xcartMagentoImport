<?php

namespace Importgen\Products;

//include(__DIR__ . "../../../html/config.php");

use \Exception;

class Simple
{
    public $product_id;
    public $name;
    public $subtitle;
    public $weight;
    public $sku;
    public $special_price;
    public $price;
    public $qty;
    public $is_in_stock;
    public $status;
    public $tax_class_id;
    public $attribute_set;
    public $type;
    public $categories;
    public $visibility;
    public $short_description;
    public $description;
    public $media_gallery = array();
    public $thumbnail;
    public $image;
    public $gender;
    public $brand;
    public $color;
    public $attributes = array();
    public $related;

    public static function createFromArray($array)
    {
        $simple = new Simple();
        $simple->product_id = $array['product_id'];
        $simple->name = $array['name'];
        $simple->updateSubtitle();
        $simple->sku = $array['sku'];
        $simple->weight = $array['weight'];
        $simple->visibility = 4;
        $simple->qty = $array['inventory'];
        $simple->description = $simple->stripEOLTags($array['long_description']);
        $simple->short_description = $simple->stripEOLTags($array['short_description']);
        $simple->brand = $array['brand'];
        $simple->color = $simple->convertLineBreakAttributes($array['color']);
        $simple->setIsInStock();
        $simple->setStatus($array['visibility']);

        $simple->setPrice($array['msrp'], $array['price']);
        $simple->setTaxClassId($array['free_tax']);
        $simple->setAttributeSet($array['type']);;
        $simple->setGender($array['gender']);
        $simple->setCategories($array['type']);

        if (isset($array['configurable_option'])) {
            $configurable_option = $simple->convertConfigurableOption($array['configurable_option']);
            $simple->addAttributeOption($configurable_option, $array['option']);
        }

        /**
         * Add images
         */
        $simple->addImages();

        return $simple;
    }

    /**
     * Updates title and subtitle
     */
    private function updateSubtitle()
    {
        if($this->name && $position=strrpos($this->name,"-"))
        {
            /**
             * @var $originalName string Store original name so that we can reference it when we create
             * substrings.
             */
            $originalName = $this->name;

            $this->name = substr($this->name, 0, $position);
            $this->subtitle = substr($originalName, $position + 1);
            //Strip trailing/leading spaces from strings.
            $this->name = trim($this->name);
            $this->subtitle = trim($this->subtitle);

        }
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

        return $simple;
    }

    public function __construct()
    {
        $this->type = "simple";
    }

    /**
     * Adds Images
     */
    public function addImages()
    {
        global $pdo;

        /**
         * @var $query string  Set up query
         */
        $query = "SELECT image_src, thumbnail, label, type
                  FROM product_images
                  WHERE product_id = :product_id";

        $stmt = $pdo->conn->prepare($query);

        $stmt->execute(array("product_id" => $this->product_id));

        /**
         * Add Images to strings/this->media_gallery array if gallery.
         */
        while ($row = $stmt->fetch()) {
            if ($row['type'] == "gallery") {
                array_push($this->media_gallery, $row['image_src']);
            } else {
                if ($row['image_src'] != '') {
                    $this->image = $row['image_src'];
                }
                if ($row['thumbnail'] != '') {
                    $this->thumbnail = $row['thumbnail'];
                }
            }
        }
    }

    /**
     * @param $configurable_option string Configurable option (e.g. waist_size)
     * @param $option string (e.g. XXL)
     */
    public function addAttributeOption($configurable_option, $option)
    {
        $this->attributes[$configurable_option] = $option;

    }

    public function getMediaGalleryString() {
        $mediaGalleryString = '';

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

    protected function setStatus($visibility)
    {
        switch ($visibility) {
            case "Y":
                $enabled = "Enabled";
                break;
            default:
                $enabled = "Disabled";
        }
        $this->status = $enabled;
    }


    /**
     * Sets Appropriate In Stock Value Based quantity
     * @param $qty integer Quantity of product
     * @throws Exception If quantity is not set, exception will be thrown.
     */
    protected function setIsInStock($qty = null)
    {
        $is_in_stock = 0;

        if (is_null($qty) && !is_null($this->qty)) {
            $qty = $this->qty;
        } else {
            throw new Exception("Quantity not set for product!  Cannot use autoload if quantity is not set");
        }

        /**
         * If quantity is greater than 0 or type is configurable, this product is in stock
         */
        if ($qty > 0 || $this->type == "configurable") {
            $is_in_stock = 1;
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

    /**
     * @param $value string Set Attribute Set
     */
    protected function setAttributeSet($value)
    {

        switch ($value) {
            case "Shorts":
            case "Pants":
            case "Jeans":
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

    protected function convertConfigurableOption($value)
    {
        switch (trim($value)) {
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

    public function getLegacySku()
    {
        $legacySku = $this->sku;
        $hyphenPosition = strrpos($this->sku, "-");

        if($hyphenPosition !== false) {
            $legacySku = substr($this->sku, 0, $hyphenPosition);
        }
        return $legacySku;
    }

    /**
     * Output as Array
     * Ref: config header row.
     */
    public function outputAsArray()
    {
        return array(
            "store" => "admin",
            "websites" => "base",
            "sku" => $this->sku,
            "name" => $this->name,
            "product_subtitle" => $this->subtitle,
            "weight" => $this->weight,
            "special_price" => $this->special_price,
            "price" => $this->price,
            //"categories" => $this->categories,
            "status" => $this->status,
            "qty" => $this->qty,
            "is_in_stock" => $this->is_in_stock,
            "tax_class_id" => $this->tax_class_id,
            "attribute_set" => $this->attribute_set,
            "type" => $this->type,
            "configurable_attributes" => "", //Configurable Attributes
            "visibility" => $this->visibility,
            "media_gallery" => $this->getMediaGalleryString(),
            "image" => "+/" . $this->image,
            "small_image" => "/" . $this->image,
            "thumbnail" => "/" . $this->thumbnail,
            "short_description" => $this->short_description,
            "description" => $this->description,
            "gender" => $this->gender,
            "feature" => "", //Feature
            "brand" => $this->brand,
            "is_featured" => "", //Is Featured
            "shoe_size" => $this->getAttributeValue('shoe_size'),
            "color" => $this->color,
            "color_spec" => $this->getAttributeValue('color_spec'),
            "size" => $this->getAttributeValue('size'),
            "inseam" => $this->getAttributeValue('inseam'),
            "waist_size" => $this->getAttributeValue('waist_size'),
            "hat_size" => $this->getAttributeValue('hat_size'),
            "team" => $this->getAttributeValue('team'),
            "simple_skus" => "", //Simple SKUS
            "manage_stock" => 1
        );
    }
}