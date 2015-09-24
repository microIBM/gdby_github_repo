<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author: liaoxianwen@ymt360.com
 * @description 与price 服务进行对接
 */
class Price_async {
    private $_service_url = '';
    // 初始
    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->library(
            array(
                'Http'
            )
        );
        $this->_service_url = C('service.price');
    }

    //商品的创建数据同步
    //和海利商量后，决定暂时禁用同步
    public function save($data) {
        return FALSE;
        $url = $this->_service_url . '/change_price';
        $return_data = $this->CI->http->query($url, $data);
        return json_decode($return_data, TRUE);
    }
}
/* End of file  price_async.php*/
/* Location: :./application/libraries/price_async.php */
