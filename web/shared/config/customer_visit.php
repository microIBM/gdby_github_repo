<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'status' => array(
        'invalid' => array(
            'code' => 0
        ),
        'plan' => array(
            'code' => 1,
            'msg'  => '计划拜访'
        ),
        'finished' => array(
            'code' => 2,
            'msg'  => '拜访完成'
        ),
    ),
    'suggestion_type' => array(
        'quality' => array(
            'code' => 1,
            'msg'  => '质量问题'
        ),
        'category' => array(
            'code' =>2,
            'msg'  => '品类问题',
        ),
        'price'    => array(
            'code' =>3,
            'msg'  => '价格问题',
        ),
        'billing'  => array(
            'code' =>4,
            'msg'  => '账期问题',
        ),
        'deliver'  => array(
            'code' =>5,
            'msg'  =>'配送问题 '
        )
    )
);
