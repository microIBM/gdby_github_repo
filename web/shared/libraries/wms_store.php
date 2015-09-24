<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Wms_store{
    private $_service_url = '';
    // 初始
    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->library(
            array(
                'Http'
            )
        );
        $this->_service_url = C('service.api');
    }

    //商品的创建数据同步
    public function create($data) {
        $url = $this->_service_url . '/odoo_stock/create_stock_picking';
        $return_data = $this->CI->http->query($url, $data);
        return json_decode($return_data, TRUE);
    }
}
