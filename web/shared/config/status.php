<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config= array(
    'municipality' => array(
        'BeiJing'   =>  1,
        'TianJin'   =>  2,
        'ShangHai'  =>  3,
        'ChongQing' =>  4
    ),
    'order' => array(
        'all'          => -1,
        'success'      => 1,
        'new'          => 2,
        'wait_confirm' => 3,
        'del'          => 0
    ),
    'user'  => array(
        'all'       => -1,
        'del'       => 0,
        'new'       => 1,
        'pass'      => 1,
        'pending'   => 2,
    ),
    'product'   => array(
        'up'    => 1,
        'down'  => 0
    ),
    'type'  => array(
        'admin'     => 0,// 管理员
        'supply'    => 1,// 供货商
        'buyer'     => 2 // 采购商
    ),
    'date'  => array(
        'today' => '今天',
        'tomorrow'  => '明天',
    ),
    'common'=> array(
        'del'           => 0,
        'success'       => 1,
        'unverified'    => 2,
        'top'           => 0,
        'disabled'      => -1,
        'normal'        => 1,
    ),
    'auth'=> array(
        'login_timeout' => -100, // 登录超时
        'forbidden'     => -2, // 没有权限
        'allow'         => 1,  // 允许操作
        'expire'        => -10 //权限过期
    ),
    'req' => array(
        'success' => 0,
        'failed'  => -1,
        'invalid' => -2, // 表单缺少必要的数据
    ),
    'sku' => array(
        'active' => 1,
        'disable' => 0,
    ),
);
