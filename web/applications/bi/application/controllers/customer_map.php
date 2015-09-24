<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * 
 * @author wangzejun@dachuwang.com
 * @version 1.0.0
 * @since 2015-08-27
 */

class Customer_map extends MY_Controller {
    const BI_URI = 'bi.dachuwang.com';
    const CUSTOMER_MAP = 5;  //与客户分析使用同一个模块

    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $sku_cate_ids = array();
        $data                    = $this->data;
        $data['city_id']         = $this->input->get_post('city_id') ?: C('open_cities.beijing.id');
        $data['menue_id']        = $this->input->get_post('menue_id');
        $data['sdate']           = $this->input->get_post('sdate') ?: date('Y-m-d',strtotime('-7 days'));
        $data['edate']           = $this->input->get_post('edate') ?: date('Y-m-d');
        $data['price_border']    = $this->input->get_post('price_border') ?: 200;
        
        //验证是否具有白名单权限
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::CUSTOMER_MAP, 'mobile' => $this->data['user_info']['mobile']));
        if (0 !== (int)$check_info['status']) {
            header('HTTP/1.0 403 Forbidden');
            $this->load->view('white_user_forbidden', $data);
        } else {
            $data['sku_cate'] = $this->format_query(self::BI_URI . '/interface_bi/get_category_child', array(), FALSE, TRUE);
            if (isset($data['sku_cate']['data']) && !empty($data['sku_cate']['data'])) {
                $sku_cate_ids = array_column($data['sku_cate']['data'], 'category_id', 'category_id');
            }
            $data['sku_cate_ids'] = $this->input->get_post('sku_cate_ids') ?: $sku_cate_ids;
            if($this->input->get_post('sku_cate_ids') !== false) {
                $data['sku_modal_tips'] = '已选择品类';
            }

            $post['sdate']        = $data['sdate'];
            $post['edate']        = $data['edate'];
            $post['city_id']      = $data['city_id'];
            $post['sku_cate_ids'] = $data['sku_cate_ids'];
            $data['customer'] = $this->format_query('order_bi/get_order_customer', $post);
            $data['load_js']  = ['customer_map_cate_check.js'];
            $this->load->view('customer_map', $data);
        }
    }
}

