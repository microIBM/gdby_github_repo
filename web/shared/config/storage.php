<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'redis_key' => array(
        'main_key_pattern'  => 'storage_{{sku_number}}_{{warehouse_id}}',
        'in_stock_key'      => 'in_stock',
        'exceed_limit_key'  => 'exceed_limit',
        'virtual_stock_key' => 'virtual_stock',
        'stock_locked_key'  => 'stock_locked',
        'wms_update_queue'  => 'sku_to_update',
        'wms_update_queue2'  => 'in_stock_update_queue', //wms 2.0的库存更新redis key
        'wms_update_bad_request_queue'  => 'in_stock_bad_request_data', //wms 2.0的库存更新redis key
    ),
    'stock_type' => array(
        'in_stock'      => 1,
        'virtual_stock' => 2,
        'exceed_limit'  => 3
    )
);
