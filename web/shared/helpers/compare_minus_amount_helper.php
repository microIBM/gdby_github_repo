<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author caochunhui@ymt360.com
 * @description 比较减免额度
 */

if(!function_exists('compare_minus_amount')) {
    function compare_minus_amount($minus_amount) {
        $limit_rmb = C('coupon_minus_limit.minus_amount');
        $real_limit_rmb = $limit_rmb / 100;
        if(intval($minus_amount) > $limit_rmb) {
            return array('status' => C('tips.code.op_failed'), 'msg' => "减免最高为{$real_limit_rmb}");
        }
        return  array();
    }
}
