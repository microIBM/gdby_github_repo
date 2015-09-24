<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config= array(
    'change_type'     => array(
        'add'    => 1,
        'minus'  => 0
    ),
    //资金变动原因
    //目前只用了前三种
    'change_source'         => array(
        'fanli'      => 0,
        'tixian'     => 1,
        'duihuan'    => 2,
        'order_sell' => 3,
        'order_buy'  => 4
    )
);
