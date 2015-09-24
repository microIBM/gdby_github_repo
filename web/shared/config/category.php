<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'category_type' => array(
        'frozen' => array(
            'code' => 198,
            'msg'  => '水产冻品'
        ),
        'vegetable' => array(
            'code' => 269,
            'msg'  => '蔬菜'
        ),
        'meat' => array(
            'code' => 326,
            'msg'  => '肉类'
        ),
        'fruit' => array(
            'code' => 43,
            'msg'  => '水果'
        ),
        //爆款订单，单独拆开
        'top_selling' => array(
            'code' => 492,
            'msg' => '爆款'
        ),
        //冻品当日不配送
        'frozen_class' => array(
            'code' => 199,
            'msg'  => '冷冻类'
        )
    ),
);
