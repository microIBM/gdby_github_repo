<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class Product extends MY_Controller {

    private $_page_size = 100;
    protected $_cities = array();

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MLine', 'MStock'));
        $this->load->library(array('product_price', 'product_lib'));
    }
   /**
     * @author: liaoxianwen@ymt360.com
     * @description 产品列表
     */
    public function lists() {
        $post = $this->post;
        $page = $this->get_page();
        $show_page = empty($_POST['itemsPerPage']) ? FALSE : TRUE;
        $ip_address = '';// 当前id地址
        if(empty($post['upid'])) {
            $this->_return_json(
                array(
                    'status'    => C('tips.code.op_failed'),
                    'msg'   => '查询条件不满足'
                )
            );
        }
        // 查询所属城市
        if(!empty($post['locationId'])) {
            $post['location_id'] = intval($post['locationId']);
        }
        // 检测用户是否已经登录,
        // 登录用户不允许切换城市
        // 优先取登录用户的所在城市
        $cur = $this->userauth->current(TRUE);
        $user_info = array();
        if($cur) {
            $post['location_id'] = $cur['province_id'];
            $local_info = $this->format_query('/location/info', array('where' => array('id' => $cur['province_id'])));
            // 所在城市info信息
            if(intval($local_info['status']) === 0) {
                $user_info = array(
                    'location_id' => $cur['province_id'],
                    'line_id' => $cur['line_id'],
                    'name' => $local_info['info']['name']
                );
            }
        }
        $customer_type = empty($cur) ? C('customer.type.normal.value') : $cur['customer_type'];
        $response = $this->format_query('/product/lists',
            array(
                'upid' => $post['upid'],
                'offset' => $page['offset'],
                'location_id' => $post['location_id'],
                'page_size' => $show_page ? $page['page_size'] : 0,
                'user_info'   => $user_info,
                'customer_type' => $customer_type,
            )
        );
        if(!empty($response['list'])) {
            $response = $this->_format_product_lists($cur, $response);
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 增加搜索，根据名称搜索
     */
    public function search() {
        $response = array(
            'status' => C('tips.code.op_failed'),
            'msg' => '暂无信息'
        );
        $page = $this->get_page();
        $cur = $this->userauth->current(TRUE);
        $location_id = C('open_cities.beijing.id');
        // 查询所属城市
        if(!empty($this->post['locationId'])) {
            $location_id = intval($this->post['locationId']);
        }
        if($cur) {
            $location_id =$cur['province_id'];
        }
        $site_id = C('app_sites.chu.id');
        $customer_type = empty($cur) ? C('customer.type.normal.value') : $cur['customer_type'];
        $visiable_arr = C('customer_visiable');
        if($customer_type == 1) {
            $visiable_condition = array($visiable_arr['all']['value'], $visiable_arr['normal']['value']);
        } else {
            $visiable_condition = array($visiable_arr['all']['value'], $visiable_arr['ka']['value']);
        }
        if(!empty($this->post['searchVal'])) {
            $fruit_category_id = empty($cur) || $cur['customer_type'] == 1 ? C("category.category_type.fruit.code") : 0 ;
            $where = array(
                'where' => array(
                    'like' => array(
                        'title' => $this->post['searchVal']
                    ),
                    'in' => array(
                        'customer_visiable' => $visiable_condition,
                    ),
                    'customer_type' => C('customer.type.normal.value'),
                    'location_id' => $location_id ,
                    'status' => C('status.product.up')
                ),
                'currentPage' => $page['page'],
                // 是否查询水果相关的产品
                'fruit_category_id' => $fruit_category_id,
                'itemsPerPage' => $page['page_size']
            );

            $response = $this->format_query('/product/manage', $where);
        }
        if(!empty($response['list'])) {
            $response = $this->_format_product_lists($cur, $response);
        }
        $this->_return_json($response);
    }

    private function _format_product_lists($cur, $products) {
        if(isset($cur['id'])) {
            $this->product_lib->format_data_by_line_id($cur, $products['list']);
            $products['list'] = $this->product_price->get_rebate_price($products['list'], $cur['id'], FALSE);
            $product_list = $this->product_lib->set_product_fields($products['list']);
            // 使用新的库存服务
            $check_storage_info = $this->format_query('/stock_service/check_storage', array('products' => $product_list, 'line_id' => $cur['line_id']));
            $this->product_lib->set_default_check_storage_list($check_storage_info, $products['list']);
        }
        $products['list'] = $this->product_lib->format_shop_product_list($products['list']);
        return $products;
    }
}
/* End of file product.php */
/* Location: :./application/controllers/product.php */
