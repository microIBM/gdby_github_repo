<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if(!function_exists('extract_spec')) {
    function extract_spec(&$data_list) {
        if ($data_list) {
            foreach($data_list as &$product_info) {
                if ( ! is_array($product_info['spec'])) {
                    $product_info['spec'] = json_decode($product_info['spec'], TRUE);
                }
                $index = array_search('规格', array_column($product_info['spec'], 'name'));
                $product_info['spec_info'] = $index ? $product_info['spec'][$index] : array();
            }
        }
    }
}
