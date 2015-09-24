<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if(!function_exists('coupon_code_create')) {
    function coupon_code_create($code_nums, $exist_codes_array = '', $code_length = 16) {
        $characters = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $promotion_codes = array();
        for($j = 0 ; $j < $code_nums; $j++) {
            $code = "";
            for($i = 0; $i < $code_length; $i++) {
                $code .= $characters[mt_rand(0, strlen($characters)-1)];
            }
            if(!in_array($code,$promotion_codes)) {
                if(is_array($exist_codes_array)) {
                    if(!in_array($code,$exist_codes_array)) {
                        $promotion_codes[$j] = $code;
                    } else {
                        $j--;
                    }
                } else {
                    $promotion_codes[$j] = $code;
                }
            } else {
                $j--;
            }
        }
        return $promotion_codes;
    }
}
