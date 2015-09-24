<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author: liaoxianwen@ymt360.com
 * @description 与erp系统进行对接
 */
class Wms_category {
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
        $url = $this->_service_url . '/odoo_category/create_category_by_path';
        $return_data = $this->CI->http->query($url, $data);
        return json_decode($return_data, TRUE);
    }
}

/* End of file  wms_category.php*/
/* Location: :./application/libraries/wms_category */
