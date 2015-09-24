<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config= array(
    'superadmin' => array(
        'admin' => array(
            'name'   =>  '超级管理员',
            'type'   =>  100
        )
    ),
    'admingroup' => array(
        'operator' => array(
            'name'   =>  '运营',
            'type'   =>  10
        ),
        'finance' => array(
            'name'   =>  '财务',
            'type'   =>  11
        ),
        'spreader' => array(
            'name'   =>  '地推',
            'type'   =>  12
        ),
        'purchase' => array(
            'name'   =>  '采购商',
            'type'   =>  20
        ),
        'supply' => array(
            'name'   =>  '供应商',
            'type'   =>  30
        ),
        'logistics' => array(
            'name'   =>  '物流',
            'type'   =>  103
        )
    ),
    'normaluser' => array(
        'purchase' => array(
            'name'   =>  '采购商',
            'type'   =>  20
        ),
        'supply' => array(
            'name'   =>  '供应商',
            'type'   =>  30
        )
    ),
    'saleuser' => array(
        'BD' => array(
            'name'   =>  '地推',
            'type'   => 12
        ),
        'BDM' => array(
            'name'   =>  '地推经理',
            'type'   => 13
        ),
        'AM' => array(
            'name'   =>  'AM',
            'type'   => 14
        ),
        'SAM' => array(
            'name'   =>  '客户经理',
            'type'   => 15
        ),
        'CM' => array(
            'name'   =>  '城市经理',
            'type'   => 16
        )
    ),
    'sale_config' => array(
        'max_customer'               => 1000,
        'max_potential_customer'     => 1000,
        'customer_protect'           => 2592000,
        'potential_customer_protect' => 2592000
    )
);
