<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$config= array(
    'basic' => array(
        'odoo_url' => 'http://wms.dachuwang.com',
        'odoo_db' => 'shengchan001',
        'odoo_username' => 'admin',
        'odoo_password' => 'xxxxxxxxxxx'
    ),
    'location' => array(
        'source' => array(
            'id'  => 18,
            'name' => '新发地' 
        ),
        'dest' => array(
            'id'  => 9,
            'name' => '销售出库'
        )
    ),
    'company' => array(
        'id' => 1,
        'name' => '大厨网'
    ),
    'partner' => array(
        'id' => 1,
        'name' => '大厨网'
    ),
    'biz2partner_map'=>array(
        '1'=>1, //大厨网
        '2'=>68 //大果网
        ),

    //线路到仓库库位操作类型的映射配置
    'line2stock_map'=>array(
        'default'=>2
    ),

    //SKU类别+线路 => 分拣类型的映射配置
    'sku_type2picking_type'=>array(
        '2'=>array(     //冻品类型
                'default'=> 116     //默认都分配到这个仓库
            )
    ),
    //分拣类型
    'picking_type' => array(
        'id' => 2,
        'name' => '销售出库'
    ),
    //单位
    'product_uom' => array(
        'id' => 1,
        'name' => '件'
    ),
    'stock_location' => array(
        array(  'id' =>12,
                'name' => '北京')
    )
);

