<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 专题信息
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class Subject extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library(array('product_price', 'product_lib'));
    }

    public function info() {
        $cur = $this->userauth->current(TRUE);
        if(!empty($_POST['id'])) {
            $subject_info = $this->format_query('/subject/info', array('id' => $_POST['id'], 'cur' => $cur));
            // KA定价策略
            if( !empty($subject_info['info']['products'])) {
                if($cur) {
                    $this->product_lib->format_data_by_line_id($cur, $subject_info['info']['products']);
                    $subject_info['info']['products'] = $this->product_price->get_rebate_price($subject_info['info']['products'], $cur['id'], FALSE);
                    $product_list = $this->product_lib->set_product_fields($subject_info['info']['products']);
                    $check_storage_info = $this->format_query('/stock_service/check_storage', array('products' => $product_list, 'line_id' => $cur['line_id']));
                    $this->product_lib->set_default_check_storage_list($check_storage_info, $subject_info['info']['products']);
                }

                $subject_info['info']['products'] = $this->product_lib->format_shop_product_list($subject_info['info']['products']);
            }
            $this->_return_json($subject_info);
        }
    }
}

/* End of file subject.php */
/* Location: ./application/controllers/subject.php */
