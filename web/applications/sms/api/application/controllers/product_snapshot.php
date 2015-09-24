<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 快照api
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-8-24
 */
class Product_snapshot extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library(
            array(
                'form_validation',
            )
        );

    }

    public function lists() {
        $this->check_validation('product', 'list', '', FALSE);
        // 商品id
        $this->form_validation->set_rules('productId', '商品ID', 'required');
        $this->validate_form();
        $page_info = $this->get_page();
        // 然后查
        $product_snapshot_lists = $this->format_query('/product_snapshot/lists', array('product_id' => $this->post['productId'], 'page' => $page_info));
        $this->_return_json($product_snapshot_lists);
    }
}

/* End of file product_snapshot.php */
/* Location: ./application/controllers/product_snapshot.php */
