<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author caochunhui@ymt360.com
 * @description 格式化price
 */

if(!function_exists('format_money')) {
    function format_money($money, $format_type = 0) {
        $money = number_format($money/100, 2);
        switch($format_type) {
        case 0:
            $money .= '元';
            break;
        case 1:
            $money = '￥' . $money;
            break;
        case 2:
            break;
        default:
            break;
        }
        return $money;
    }
}
