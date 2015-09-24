<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'status' => array(
        'valid' => array(
            'code' => 1,
            'msg' => '有效'
        ),
        'invalid' => array(
            'code' => 0,
            'msg' => '无效'
        )
    ),
    'type' => array(
        'meet_amount_and_minus' => array(
            'code' => 1,
            'msg'  => '满减'
        ),
        'continus_order_and_return_ticket' => array(
            'code' => 2,
            'msg'  => '连续下单返券'
        )
    )
);
