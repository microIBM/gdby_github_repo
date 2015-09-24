<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * a 定制化的按单数来提供补贴的模式，必须提供返现的单号
 *   比如当天的前五单返现，那么必须将1,2,3,4,5列在deal_counter中
 * b 每单都补贴的模式，可以设置上限
 */

$config = array(
    //供应商补贴规则
    //这里的补贴单位都是元
    'buy' =>
    array(
        'type'  => 'a',
        'rules' => array(
            'first_order_return'        => 40,   //首单返现额度
            'first_order_return_switch' => 'off', //首单返现开关,合法值得为on off
            'return_amount'             => 10,   //非首单返现额度
            'return_switch'             => 'off',
            'single_order_least_amount' => 200,  //返现满足额度条件，即必须满足订单总额>=这个数才返现
            'deal_counter'              => array(
                1, 2, 5 //每天返现的订单计数
            ),
        ),
        'limits'                 => array(
            'daily_return_limit' => 100,
        )
    ),
    //采购商补贴规则
    //补贴单位是元
    'sell' =>
    array(
        'type' => 'b',
        'rules'                  => array(
            'return_amount'      => 10,
            'return_switch'      => 'off',
            'single_order_least_amount' => 200,  //返现满足额度条件，即必须满足订单总额>=这个数才返现
        ),
        'limits'                 => array(
            'daily_return_limit' => 100,
        )
    ),
    'promote' =>
    array(
        'type' => 'b',
        'rules'             => array(
            'first_order_return'        => 5,   //首单返现额度
            'first_order_return_switch' => 'off', //首单返现开关,合法值得为on off
            'return_amount' => 0,
            'return_switch' => 'off',
            'single_order_least_amount' => 200,  //返现满足额度条件，即必须满足订单总额>=这个数才返现
        ),
        'limits'            => array(
            'daily_return_limit' => 100000,
        )
    )

);

