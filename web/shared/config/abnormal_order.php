<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'otype' => array(
        'restock'  => array(
            'name' => '补货单',
            'val'  => 1
        ),
        'return'   => array(
            'name' => '退货退款单',
            'val'  => 2
        ),
        'refund'   => array(
            'name' => '退款单',
            'val'  => 3
        )
    ),
    'status'  => array(
        'processing' => array(
            'msg'    => '处理中',
            'code'   => 1
        ),
        'finish'     => array(
            'msg'    => '已完成',
            'code'   => 2
        )
    ),
);
