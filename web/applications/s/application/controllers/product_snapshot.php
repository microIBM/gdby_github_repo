<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 商品快照服务
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 15-8-21
 */
class Product_snapshot extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(array('MProduct_snapshot', 'MProduct', 'MUser'));
        $this->load->library(array('product_lib'));
        $this->_req_time = $this->input->server('REQUEST_TIME');
    }

    public function lists() {
        // 填充where 条件
        $where = $this->_fill_where();
        // 填充分页
        $page = $this->_fill_page();
        extract($page);
        $product_snapshots = $this->MProduct_snapshot->get_lists('*',
            $where,
            array('created_time' => 'desc'),
            array(),
            $offset,
            $page_size
        );
        $total = $this->MProduct_snapshot->count($where);
        if(!empty($product_snapshots)) {
            $product_snapshots = $this->_modify_snapshot_data($product_snapshots);
        }
       // 格式化数据内容
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list'   => $product_snapshots,
                'total'  => $total
            )
        );
    }

    private function _modify_snapshot_data($product_snapshots) {
        // 变更数据结构让其可以使用product_lib
        $product_snapshots = $this->_format_snapshots($product_snapshots, array('key' => 'id', 'tmp_key' => 'origin_id', 'use_key' => 'product_id'));
        $this->product_lib->format_sms_product_data($product_snapshots);
        // 还原数据结构
        $product_snapshots = $this->_format_snapshots($product_snapshots, array('key' => 'origin_id', 'tmp_key' => 'product_id', 'use_key' => 'id'));
        // 获取操作人的信息
        $product_snapshots = $this->_get_operator($product_snapshots);
        return $product_snapshots;

    }

    private function _get_operator($product_snapshots) {
        $op_user_ids = array_unique(array_column($product_snapshots, 'op_user_id'));
        $users = $this->MUser->get_lists('id, name, mobile', array('in' => array('id' => $op_user_ids)));
        $users = array_column($users, NULL, 'id');
        foreach($product_snapshots as &$snapshot) {
            $op_user_id = $snapshot['op_user_id'];
            $snapshot['operator'] = isset($users[$op_user_id]['name']) ? $users[$op_user_id]['name'] . "({$users[$op_user_id]['mobile']})" : '';
        }
        return $product_snapshots;
    }

    private function _format_snapshots($product_snapshots, $key_params) {
        if($product_snapshots) {
            $tmp_product_snapshots = [];
            extract($key_params);
            foreach($product_snapshots as $snapshot) {
                $snapshot_id = $snapshot[$key];
                unset($snapshot[$key]);
                $snapshot[$key] = $snapshot[$use_key];
                $snapshot[$tmp_key] = $snapshot_id;
                $tmp_product_snapshots[] = $snapshot;
            }
            $product_snapshots = $tmp_product_snapshots;
        }
        return $product_snapshots;
    }

    private function _fill_where() {
        if(isset($_POST['product_id'])) {
            $where['product_id'] = $_POST['product_id'];
        }
        return $where;
    }

    private function _fill_page() {
        if(isset($_POST['page'])) {
            $page = $_POST['page'];
        } else {
            $page = $this->get_page();
        }
        return $page;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 快照创建
     * @method post
     * @param product_id, user_id
     */
    public function create(){
        // 商品原id
        $origin_product_info = $this->MProduct->get_one('*', array('id' => $_POST['product_id']));
        $response = array(
            'status' => C('tips.code.op_success'),
            'msg' => '快照保存成功'
        );

        if($origin_product_info && isset($_POST['user_id'])) {
            $origin_product_info['op_user_id'] = $_POST['user_id'];
            $format_product_snapshot_data = $this->_format_product_snapshot_data($origin_product_info);
            extract($format_product_snapshot_data);
            $product_snapshot_id = $this->MProduct_snapshot->create($product_snapshot);
        } else {
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => '没有此商品'
            );
        }
        $this->_return_json($response);
    }

    public function create_batch() {
        // 商品原id
        $origin_product_lists = $this->MProduct->get_lists('*', array('in' => array('id' => $_POST['product_ids'])));
        $response = array(
            'status' => C('tips.code.op_success'),
            'msg' => '没有此商品'
        );
        !$origin_product_lists || !isset($_POST['user_id']) AND $this->_return_json($response);
        $product_snapshots = [];
        foreach($origin_product_lists as $origin_product_info) {
            $origin_product_info['op_user_id'] = $_POST['user_id'];
            $format_product_snapshot_data = $this->_format_product_snapshot_data($origin_product_info);
            extract($format_product_snapshot_data);
            $product_snapshots[] = $product_snapshot;
        }
        $success = $this->MProduct_snapshot->create_batch($product_snapshots);
        if($success) {
            $response = array(
                'status' => C('tips.code.op_success'),
                'msg' => '快照保存成功'
            );
        }
        $this->_return_json($response);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 格式化快照的信息
     */
    private function _format_product_snapshot_data($origin_product_info) {
        // 需要记录当前用户的操作行为
        $product_snapshot = array(
            'title'                => $origin_product_info['title'],
            'category_id'          => $origin_product_info['category_id'],
            'product_id'           => $origin_product_info['id'],
            'adv_words'            => $origin_product_info['adv_words'],
            'location_id'          => $origin_product_info['location_id'],
            'customer_type'        => $origin_product_info['customer_type'],
            'collect_type'         => $origin_product_info['collect_type'],
            'price'                => $origin_product_info['price'],
            'market_price'         => $origin_product_info['market_price'],
            'single_price'         => $origin_product_info['price'],
            'storage'              => $origin_product_info['storage'],
            'buy_limit'            => $origin_product_info['buy_limit'],
            'line_id'              => $origin_product_info['line_id'],
            'visiable'             => $origin_product_info['visiable'],
            'is_round'             => $origin_product_info['is_round'],
            'unit_id'              => $origin_product_info['unit_id'],
            'close_unit'           => $origin_product_info['unit_id'],
            'spec'                 => $origin_product_info['spec'],
            'status'               => $origin_product_info['status'],
            'created_time'         => $this->_req_time,
            'updated_time'         => $this->_req_time,
            'sku_number'           => $origin_product_info['sku_number'],
            'sku_number'           => $origin_product_info['sku_number'],
            // 客户类型的可见性
            'customer_visiable'    => $origin_product_info['customer_visiable'],
            'user_id'              => $origin_product_info['user_id'],
            'op_user_id'           => $origin_product_info['op_user_id']
        );
        return array(
            'product_snapshot' => $product_snapshot
        );

    }
}

/* End of file product_snapshot.php */
/* Location: ./application/controllers/product_snapshot.php */
