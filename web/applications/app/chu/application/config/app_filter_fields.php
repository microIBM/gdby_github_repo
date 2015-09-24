<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
$config = array(
    'product'         => array(
        'lists' => array(
            'list' => array(
                'id'            => '',
                'category_id'   => '',
                'title'         => '',
                'adv_words'     => '',
                'price'         => '',
                'status'        => '',
                'created_time'  => '',
                'updated_time'  => '',
                'sku_number'    => '',
                'location_id'   => '',
                'storage'       => '',
                'buy_limit'     => '',
                'customer_type' => '',
                'unit'          => '',
                'storage_cn'    => '',
                'buy_limit_cn'  => '',
                'location_name' => '',
                'spec'          => array(
                    'id'        => '',
                    'name'      => '',
                    'val'       => '',
                ),
                'pictures'      => array(
                    'id'        => '',
                    'pic_url'   => '',
                    'file_size' => ''
                ),
                'big_imgs'      => array(
                    'id'        => '',
                    'pic_url'   => '',
                    'file_size' => ''
                )
            ),
        ),
    ),
    'order' => array(
        'lists' => array(
            'orderlist' => array(
                'id'            => '',
                'status_cn'     => '',
                'status'        => '',
                'final_price'   => '',
                'updated_time'  => '',
                'username'      => '',
                'pay_type'      => '',
                'deliver_date'  => '',
                'order_number'  => '',
                'total_price'   => '',
                'mobile'        => '',
                'deliver_fee'   => '',
                'customer_type' => '',
                'pay_status'    => '',
                'minus_amount'  => '',
                'pay_type_cn'   => '',
                'suborders'     => array(
                    'deliver_fee' => '',
                    'final_price' => '',
                    'updated_time'=> '',
                    'customer_type'=> '',
                    'id'          => '',
                    'username'    => '',
                    'details'     => array(
                        'spec'    => array(
                            'id'  => '',
                            'name'=> '',
                            'val' => ''
                        ),
                        'id'      => '',
                        'status'  => '',
                        'price'   => '',
                        'name'    => '',
                        'quantity'=> '',
                        'updated_time' => '',
                        'created_time' => '',
                        'single_price' => '',
                    ),
                    'order_number' => '',
                    'status'       => '',
                    'minus_amount' => '',
                    'location_id'  => '',
                    'created_time' => '',
                ),
                'location_id'   => '',
                'deliver_time'  => '',
                'created_time'  => '',
            )
        ),
        'today_bought_products' => array(
            'status'          => '',
            'msg'             => '',
            'check_cart_info' => ''
        ),
        'get_wx_order_info' => array(
            'data' => array(
                'id' => '',
                'username' => '',
                'user_id'  => '',
                'status'   => '',
                'created_time' => '',
                'updated_time' => '',
                'total_price'  => '',
                'location_id'  => '',
                'minus_amount' => '',
                'final_price'  => '',
                'deliver_fee'  => '',
                'customer_type'=> ''
            )
        ),
    ),

    //
    'recommend'       => [],

    //广告
    'ads'             => array(
        'lists' => array(
            'id'           => '',
            'title'        => '',
            'location_id'  => '',
            'link_url'     => '',
            'pic_url'      => '',
            'status'       => '',
            'customer_type'=> ''
        )
    ),

    //分类
    'category'        => array(
        'cate_lists' => array(
            'list' => array(
                'top' => array(
                    'id' => '',
                    'name' => '',
                    'status' => '',
                    'created_time' => '',
                    'updated_time' => '',
                    'site_id'      => '',
                    'location_id'  => '',
                    'customer_type'=> '',
                ),
                'second' => array(
                    'id'            => '',
                    'upid'          => '',
                    'name'          => '',
                    'status'        => '',
                    'created_time'  => '',
                    'updated_time'  => '',
                    'site_id'       => '',
                    'location_id'   => '',
                    'customer_type' => ''
                ),
            ),
        ),
        'lists' => array(
            'list' => array(
                'top' => array(
                    'id'            => '',
                    'name'          => '',
                    'status'        => '',
                    'created_time'  => '',
                    'updated_time'  => '',
                    'location_id'   => '',
                    'customer_type' => ''
                ),

                'second' => array(
                    'name' => '',
                ),
            ),
            'recommends' => array(
                'id'            => '',
                'title'         => '',
                'location_id'   => '',
                'status'        => '',
                'created_time'  => '',
                'updated_time'  => '',
                'customer_type' => ''
            ),
        ),
    ),
    'location'        => [],
    'message'         => array(
        'get_unread_messages' => array(
            'id'    => '',
            'msg_type' => '',
            'url'      => '',
            'content'  => '',
            'extra'    => '',
            'receive_time' => '',
            'title'        => '',
            'status'       => ''
        )
    ),
    'customer'        => array(
            'baseinfo' => array(
                'info' => array(
                    'id' => '',
                    'name' => '',
                    'shop_name' => '',
                    'username'  => '',
                    'mobile'    => '',
                    'address'   => '',
                    'status'    => '',
                    'customer_type' => '',
                    'created_time'  => '',
                    'updated_time'  => ''
                ),
                'order' => array(
                    '0' => '',
                    '1' => '',
                    '2' => '',
                    '100' => '',
                ),
        ),
    ),
    'coupon' => array(
        'lists' => array(
            'id' => '',
            'status' => '',
            'status_cn' => '',
            'minus_amount' => '',
            'created_time' => '',
            'updated_time' => '',
            'detail' => array(
                'minus_amount' => '',
                'valid_time'   => '',
                'invalid_time' => '',
                'description'  => '',
            ),
        ),
    ),
    'subject'         => [],
    'v2' => array(
        'order' => array(
            'lists' => array(
                'orderlist' => array(
                    'id'            => '',
                    'status'        => '',
                    'order_number'  => '',
                    'product_total' => '',
                    'suborders_tatal'=> '',
                    'final_price'   => '',
                    'deliver_date'  => '',
                    'order_number'  => '',
                    'total_price'   => '',
                    'mobile'        => '',
                    'deliver_fee'   => '',
                    'customer_type' => '',
                    'pay_status'    => '',
                    'minus_amount'  => '',
                    'pay_type_cn'   => '',
                    'suborders'     => array(
                        'deliver_fee' => '',
                        'final_price' => '',
                        'updated_time'=> '',
                        'customer_type'=> '',
                        'id'          => '',
                        'username'    => '',
                        'details'     => array(
                            'spec'    => array(
                                'id'  => '',
                                'name'=> '',
                                'val' => ''
                            ),
                            'id'      => '',
                            'status'  => '',
                            'price'   => '',
                            'name'    => '',
                            'quantity'=> '',
                            'updated_time' => '',
                            'created_time' => '',
                            'single_price' => '',
                        ),
                        'order_number' => '',
                        'status'       => '',
                        'minus_amount' => '',
                        'location_id'  => '',
                        'created_time' => '',
                    ),
                    'location_id'   => '',
                    'deliver_time'  => '',
                    'created_time'  => '',
                )
            ),
        ),

    ),
);
