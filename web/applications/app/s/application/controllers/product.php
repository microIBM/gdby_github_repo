<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 商品服务
 * @author: liaoxianwen@ymt360.com
 * @version: 2.0.0
 * @since: 2015-3-3
 */
class Product extends MY_Controller {

    public $units = array();
    public $product_status = array();

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MCategory',
                'MProduct',
                'MSku',
                'MLocation',
                'MWorkflow_log',
                'MOrder',
                'MOrder_detail',
                'MProperty'
            )
        );
        $this->units = C('unit');
        $this->product_status = C('product');
        $this->load->library(array('Cate_logic','product_lib'));
        $this->load->helper(array('img_zoom'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取单个商品信息
     */
    public function info() {
        $info = $this->MProduct->get_one('*', array('id' => $_POST['id']));
        // 获取分类信息
        if($info) {
            $cate = $this->MCategory->get_one('name,path', array('id' => $info['category_id']));
            $info['cate_info'] = $cate['name'];
            $info['path'] = explode('.', trim($cate['path'], '.'));
            $info['price'] = $info['price'] / 100;
            $info['market_price'] = $info['market_price'] / 100;
            $info['single_price'] = $info['single_price'] / 100;
            $info['unit_name'] = $this->product_lib->get_unit_name($info['unit_id']);
            $info['close_unit_name'] = $this->product_lib->get_unit_name($info['close_unit']);
            // 获取日志
            $info['op_logs'] = $this->MWorkflow_log->get_lists('*', array('obj_id' => $info['id']), array('id' => 'desc'));
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'info'   => $info
            )
        );
    }
    /**
     * 所属线路
     * @author: liaoxianwen@ymt360.com
     * @description 获取列表
     */
    public function lists() {
        $products = array();
        $cate_ids= explode(',', rtrim($_POST['upid'], ','));
        $childs = $this->cate_logic->get_child($cate_ids);
        if($childs) {
            $category_ids = array_column($childs, "id");
        }
        foreach($cate_ids as $v) {
            $category_ids[] = $v;
        }
        if(empty($_POST['location_id'])) {
            $_POST['location_id'] = C('open_cities.beijing.id');
        }
        $page_size = isset($_POST['page_size']) ? intval($_POST['page_size']) : 100;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        $customer_type = empty($_POST['customer_type']) ? C('customer.type.normal.value') : intval($_POST['customer_type']);
        // 查询列表, visiable =3 位不可见 1为全部可见，2为部分可见
        $lists = $this->MProduct->get_lists('*',
            array(
                'in' => array(
                    'category_id' => $category_ids,
                ),
                'location_id' => $_POST['location_id'],
                'customer_type' => $customer_type,
                'status' => C('status.product.up'),
                'visiable !=' => C('visio.none')
            ),
            array('updated_time' => 'desc'),
            array(),
            $offset,
            $page_size
        );
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $lists
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 更新库存，限购
     */
    public function update_storage() {
        if(!empty($_POST['data']) && is_array($_POST['data'])) {
            foreach($_POST['data'] as $data) {
                $where = array('id' => $data['id']);
                $product = array('storage' => $data['storage']);
                $this->MProduct->update_info($product, $where);
            }
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'msg' => '保存成功'
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 后台管理接口
     */
    public function manage() {
        $page = $this->get_page();
        $where = array();
        if(!empty($_POST['where'])) {
            $where = $_POST['where'];
        }
        $total =  $this->MProduct->count($where);
        $products = $this->MProduct->get_lists(
            array(),
            $where,
            array('id' => 'DESC'),
            array(),
            $page['offset'],
            $page['page_size']
        );
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $products,
                'total' => $total
            )
        );
    }

    public function units() {
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $this->units
            )
        );
    }
    /**
     * 获取当天所购买的商品，有限购的，就返回，没有就返回为空
     * 当前时间，若是大于23点得，那么就只查23点-24点之间是否下过订单，
     * 切订单里有限购的，若是小于23，那么就是从0点后到23点之间是否有下单有限购
     * @author: liaoxianwen@ymt360.com
     * @description
     */
    public function get_today_bought_products() {
        $request_time =  $this->input->server('REQUEST_TIME');
        $hour = intval(date('H', $request_time));
        $user_info = $_POST['user_info'];
        // 23点
        if($hour == 23) {
            $min_date = strtotime(date('Y-m-d', $request_time) . '23:00');
            $max_date = $request_time;
        } else {
            // 其他点数
            $min_date = strtotime(date('Y-m-d', strtotime('-1 day')) . '23:00');
            $max_date = $request_time;
        }
        if(isset($user_info['id'])) {
            $where = array(
                'user_id' => $user_info['id'],
                'created_time >=' => $min_date,
                'created_time >=' => $min_date,
                'status !=' => C('status.common.del')
            );
            $orders = $this->MOrder->get_lists('*', $where);
            if($orders) {
                $order_ids = array_column($orders, 'id');
                $details = $this->MOrder_detail->get_lists('product_id, quantity', array('in' => array('order_id' => $order_ids)));
                $return = array('list' => $details, 'status' => C('tips.code.op_success'));
            } else {
                $return = array('status' => C('tips.code.op_success'), 'msg' => '无限购产品');
            }
        } else {

            $return = array('status' => C('tips.code.op_success'), 'msg' => '无限购产品');
        }

        $this->_return_json($return);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取用户经常购买
     */
    public function get_always_buy_products() {
        $page = $this->get_page();
        $response = array(
            'status' => C('tips.code.op_success'),
            'list' => array(),
            'msg' => '您还未下过单'
        );
        if( !empty($_POST['location_id']) && !empty($_POST['user_id']) && !empty($_POST['customer_type'])) {
            $order = $this->MOrder->get_lists__Cache60('id', array('user_id' => $_POST['user_id']));
            if(!empty($order)) {
                $order_ids = array_column($order, 'id');
                $detail = $this->MOrder_detail->get_lists__Cache10('*', array('in' =>array('order_id' => $order_ids)));
                $sku_numbers = array_column($detail, 'sku_number');
                $where = array(
                    'in' => array(
                        'sku_number' => $sku_numbers,
                    ),
                    'location_id' => $_POST['location_id'],
                    'customer_type' => $_POST['customer_type'],
                    'status' => C('status.product.up'),
                    'visiable !=' => C('visio.none')
                );
                $total = $this->MProduct->count($where);
                // 查询列表, visiable =3 位不可见 1为全部可见，2为部分可见
                $lists = $this->MProduct->get_lists__Cache30('*',
                    $where,
                    array('updated_time' => 'desc'),
                    array(),
                    $page['offset'],
                    $page['page_size']
                );
                $response = array(
                    'status' => C('tips.code.op_success'),
                    'list' => $lists,
                    'total' => $total
                );
            }
        } else {
            $response['msg'] = '提交参数错误';
        }
        $this->_return_json($response);
    }

    public function get_lists_by_ids() {
        $fields = isset($_POST['fields']) ? $_POST['fields'] : '*';
        if(!empty($_POST['fields']) && is_array($_POST['fields'])) {
            $fields = implode(',', $_POST['fields']);
        }
        if(!empty($_POST['ids']) && is_array($_POST['ids'])) {
            $where = array(
                'in' => array('id' => $_POST['ids'])
            );
            $products = $this->MProduct->get_lists($fields, $where);
            $response = array(
                'status' => C('tips.code.op_success'),
                'list' => $products
            );
        } else {
            $response = array(
                'status' => C('tips.code.op_failed'),
                'msg' => '商品ids 必须是数组'
            );
        }
        $this->_return_json($response);
    }

}

/* End of file product.php */
/* Location: ./application/controllers/product.php */
