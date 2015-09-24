<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @author: liaoxianwen@ymt360.com
 * @description 均摊减免
 */
if(!function_exists('devide_minus')) {
    // objects 需要做处理的数据对象
    // total_price 对子订单均摊时，[母订单金额]或者对子订单下面的sku均摊时，[子订单金额]
    // minus_amount 减免信息
    function devide_minus($objects, $total_price, $minus_amount) {
        if(is_array($objects) && $objects && $minus_amount) {
            $formated_minus_amount = 0;
            $length = count($objects);
            foreach($objects as $key => &$v) {
                if($key < $length-1) {
                    $v['minus_amount'] = ceil($minus_amount * $v['total_price'] / $total_price);
                    $formated_minus_amount += $v['minus_amount'];
                } else {
                    $v['minus_amount'] = $minus_amount - $formated_minus_amount;
                }
            }
        }
        return $objects;
    }
}
