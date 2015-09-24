<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('set_sku')) {
    function set_sku($product_id) {
        return 1000000 + $product_id;
    }
}
