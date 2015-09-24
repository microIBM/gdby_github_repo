<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// BI系统导航配置
$config = array(
    //菜单id,菜单标题,菜单url,是否支持全国统计
    'left_nav' => array(
        array(
            'id' => 1,
            'title' => '数据统计',
            'url' => 'statics',
            'whole' => TRUE
        ),
        array(
            'id' => 2,
            'title' => '货物销量',
            'url' => 'statics/sku_rank',
            'whole' => FALSE
        ),
        array(
            'id' => 3,
            'title' => '订单分时',
            'url' => 'statics/order_td',
            'whole' => FALSE
        ),
        array(
            'id' => 4,
            'title' => '客户分析',
            'url' => 'statics/customer_info',
            'whole' => FALSE
        ),
        array(
            'id' => 5,
            'title' => 'Saiku品类分析',
            'url' => 'saiku',
            'whole' => TRUE
        ),
        array(
            'id'=> 6,
            'title' => 'SKU分析',
            'url' => 'statics_sku',
            'whole' => FALSE
        ),
        array(
            'id'=> 7,
            'title' => '品类分析',
            'url' => 'statics_cate',
            'whole' => FALSE
        ),
        array(
            'id'=>8,
            'title' => '竞品分析',
            'url' => 'spider_anti',
            'whole'=>FALSE
        ),
        array(
            'id'=>10,
            'title' => '客户分布图',
            'url' => 'customer_map',
            'whole'=>FALSE
        ),
        array(
            'id'=>9,
            'title' => '白名单管理',
            'url' => 'white_user',
            'whole'=>TRUE
        )
        
        //array('id'=>6,'title' => 'BD业绩','url' => 'statics/bd_statics','whole'=>FALSE),
    ),
    'top_nav' => array(
       '1' => '大厨网',
       '2' => '大果网',
    ),
);
