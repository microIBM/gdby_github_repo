<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 货物的模型
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class Product extends MY_Controller {

    private $_page_size = 10;
    protected $_cities = array();

    public function __construct() {
        parent::__construct();
        $this->load->library(array('Cate_logic', 'Price_async', 'redisclient', 'Product_lib'));
        $this->load->model(array('MWorkflow_log'));
    }
   /**
     * @author: liaoxianwen@ymt360.com
     * @description 设置货物状态
     */
    public function set_status() {
        $this->check_validation('product', 'edit', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $post = $this->post;
        $where = array(
            'id'    => $post['id'],
        );
        $product_info = $this->_check_product_info($post);
        $response = $this->_create_snapshot(array('status' => C('tips.code.op_success')), $product_info['info'], $cur);
        $response =  $this->format_query('/product/set_status',array('where' => $where, 'status' => $post['status']));
        $this->_return_json($response);
    }

    private function _check_product_info($post) {
        $product_info  = $this->format_query('/product/info', $post);
        if(empty($product_info['info'])) {
            $this->_return_json(array('status' => C('tips.code.op_failed'), 'msg' => '商品不存在'));
        }
        return $product_info;
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 管理货物
     */
    public function manage() {
        $post  = $this->post;
        $post['where'] = array();
        if(!empty($post['searchVal'])) {
            $pattern = '/^1(\d+){6}$/';
            if(preg_match($pattern, $post['searchVal'])) {
                $post['where'] = array('sku_number' => $post['searchVal']);
            } else {
                $post['where'] = array('like' => array('title' => $post['searchVal']));
            }
        }
        if(isset($post['status'])) {
            if($post['status'] != 'all') {
                $post['where']['status'] = $post['status'];
            }
            unset($post['status']);
        } else {
            $post['where']['status'] = C('status.common.success');
        }
        if(empty($post['locationId'])) {
            $post['where']['location_id'] = C('open_cities.beijing.id');
        } else {
            $post['where']['location_id'] = $post['locationId'];
            unset($post['locationId']);
        }
        if(empty($post['customerType'])) {
            $post['where']['customer_type'] = C('customer.type.normal.value');
        } else {
            $post['where']['customer_type'] = $post['customerType'];
            unset($post['customerType']);
        }
        // 若是运营人员，那么应该可以看到所有的货物
        $products = $this->format_query('/product/manage', $post);
        if(!empty($products['list'])) {
            $products['list'] = $this->product_lib->format_sms_product_data($products['list']);
        }
        $this->_set_location($products);
        $this->_return_json($products);
    }

    private function _set_location(&$data) {
        $location = $this->format_query('/location/get_child');
        $data['location'] = $location['list'];
        $data['customer_type_options'] = array_values(C('customer.type'));
        $data['collect_type_options'] = array_values(C('foods_collect_type.type'));
    }

    private function _set_lines(&$data) {
        $lines = $this->format_query('/line/get_all');
        $new_lines = array();
        foreach($lines['list'] as $v) {
            $new_lines[$v['location_id']][] = $v;
        }
        $data['line_options'] = $new_lines;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 设置可见范围
     */
    private function _set_visiable(&$data) {
        $data['visiable_options'] = C('visiable');
        $data['customer_type_options'] = array_values(C('customer.type'));
        $data['customer_visiable_options'] = array_values(C('customer_visiable'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 物品信息维护
     */
    public function edit() {
        $this->check_validation('product', 'edit', '', FALSE);
        $post = $this->post;
        $product_info = $this->_check_product_info($post);
        $this->_return_json($product_info);
    }

    public function detail() {
        $this->check_validation('product', 'edit', '', FALSE);
        $post = $this->post;
        $product_info = $this->_check_product_info(array('id' => $post['id']));
        // 快照列表,详情只需要取当前最新的快照
        $product_snapshots = $this->format_query('/product_snapshot/lists', array('product_id' => $post['id'], 'itemsPerPage' => 1, 'fields' => array('op_user_id', '')));
        $product_info['info']['workflow_log'] = [];
        if($product_snapshots['status'] == 0 && isset($product_snapshots['list'][0])) {
            $product_info['info']['workflow_log'] = [
                'operator' => $product_snapshots['list'][0]['operator'],
                'created_time' => date('Y-m-d H:i:s',$product_snapshots['list'][0]['created_time'])
            ];
        }
        $this->_return_json($product_info);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 更具sku获取信息
     */
    public function get_sku_info() {
        if(isset($this->post['skuNumber'])) {
            $where['sku_number'] = $this->post['skuNumber'];
        } else if(isset($this->post['id'])) {
            $where['id'] = $this->post['id'];
        }
        // 获取sku信息，需要是已经
        $where['status'] = C('status.common.success');
        $condition = array('where' => $where);
        $data = $this->format_query('/sku/info', $condition);
        // 设置城市选项
        $this->_set_location($data);
        $this->_set_lines($data);
        // 设置商品可见性范围选项
        $this->_set_visiable($data);
        $this->_return_json($data);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 增加货物
     */
    public function save() {
        $this->check_validation('product', 'create', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        // 设置post过来的值
        $post_product_data = $this->_fill_post_product_data();
        // 更新原来的
        if(!empty($post_product_data['id'])) {
            // 更新商品
           $response = $this->_update_product($post_product_data, $cur);
        } else {
            // 创建新的商品
            $response = $this->_create_new_product($post_product_data, $cur);
        }
        $this->_return_json($response);
    }

    private function _update_product($post_product_data, $cur) {
        $product_id = $post_product_data['id'];
        $product_info = $this->_check_product_info(array('id' => $product_id));
        $response = array(
            'status' => C('tips.code.op_success'),
            'msg' => '商品保存成功'
        );
        $modified_info = [];
        // 若商品信息没有变化[]
        !empty($product_info['info']) AND $modified_info = $this->_get_modified_info($post_product_data, $product_info['info']);
        $modified_info AND $response = $this->_create_snapshot($response, $post_product_data, $cur);
        $modified_info AND $response = $this->_update_product_info($modified_info, $product_id);

        return $response;
    }

    private function _create_snapshot($response, $post_product_data, $cur) {
        if(isset($response['status']) && intval($response['status']) === 0) {
            $product_id = $post_product_data['id'];
            // 记录用户信息
            $product_snapshot_info['product_id'] = $product_id;
            // 操作人id
            $product_snapshot_info['user_id'] = $cur['id'];
            // 更新商品信息 & 记录商品的快照
            $response['create_snapshot_response'] = $this->_create_product_snapshot($product_snapshot_info);
        }
        return $response;
    }

    private function _fill_post_product_data() {
        $post = $this->post;
        $post['customerType'] = empty($post['customerType']) ? C('customer.type.normal.value') : intval($post['customerType']);
        $post['collectType'] = empty($post['collectType']) ? C('foods_collect_type.type.pre_collect.value') : intval($post['collectType']);
        $post['customerVisiable'] = isset($post['customerVisiable']) ? $post['customerVisiable'] : C('customer_visiable.all.value');
        return $post;
    }
    // 比对两个数组的值来获取新的更新信息
    private function _get_modified_info($post, $product_info) {
        $modified_info = array();
        if($product_info['title'] != $post['title']
            || $product_info['unit_name'] != $post['unitName']
            || $product_info['close_unit_name'] != $post['closeUnit']
            || $product_info['location_id'] != $post['locationId']
            || $product_info['line_id'] != $post['lineId']
            || $product_info['customer_type'] != $post['customerType']
            || $product_info['collect_type'] != $post['collectType']
            || $product_info['is_round'] != $post['isRound']
            || $product_info['visiable'] != $post['visiable']
            || $product_info['buy_limit'] != $post['buyLimit']
            || $product_info['customer_visiable'] != $post['customerVisiable']
            || $product_info['adv_words'] != $post['advWords']) {
                // 有变化
                $modified_info = $this->_filter_post_product_data($post);
            }
        return $modified_info;
    }

    private function _update_product_info($modified_info, $product_id) {
        $modified_info['id'] = $product_id;
        $response = $this->format_query('/product/save', $modified_info);
        $response['set_product_nums_response'] = $this->format_query('/catemap/set_product_nums', array('product' => $modified_info));
        return $response;
    }

    private function _create_product_snapshot($product_snapshot_info) {
        $return_data = $this->format_query('/product_snapshot/create', $product_snapshot_info);

        return $return_data;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 格式化新商品
     */
    private function _create_new_product($post, $cur) {
        $product_info = $this->_filter_post_product_data($post);
        $response = $this->format_query('/product/create', $product_info);
        if(!empty($response['info'])) {
            $product_info['id'] = $response['info']['id'];
        }
        $this->_add_product_into_stock($response, $product_info);
        $response = $this->_create_snapshot($response, $product_info, $cur);
        return $response;
    }

    private function _filter_post_product_data($post) {
        $product_info = array(
            'unit_name'         => $post['unitName'],
            'close_unit'        => $post['unitName'],
            'customer_type'     => $post['customerType'],
            'collect_type'      => $post['collectType'],
            'title'             => $post['title'],
            'location_id'       => $post['locationId'],
            'sku_number'        => $post['skuNumber'],
            'status'            => $post['status'],
            'adv_words'         => $post['advWords'],
            'storage'           => empty($post['storage']) ? -1 : $post['storage'] ,
            'buy_limit'         => $post['buyLimit'],
            'line_id'           => empty($post['lineId']) ? 0 : $post['lineId'],
            'visiable'          => empty($post['visiable']) ? 1 : $post['visiable'],
            'customer_visiable' => $post['customerVisiable'],
            'is_round'          => $post['isRound'],
            'price'             => $post['price'] * 100,
            'market_price'      => $post['marketPrice'] * 100,
            'single_price'      => $post['price'] * 100
        );
        return $product_info;
    }

    private function _add_product_into_stock($data, $post_data) {
        if(!empty($data['info'])) {
            $response_warehouse_info = $this->format_query('/line/get_warehouses', array('location_id' => $post_data['location_id'], 'line_id' => $post_data['line_id']));
            // 检测是否有仓库
            if($response_warehouse_info['list']) {
                $warehouse_ids = array_values(array_unique(array_column($response_warehouse_info['list'], 'warehouse_id')));
                $response_info = $this->format_query('/stock_service/set_sku_initial_stock', array('sku_number' => $data['info']['sku_number'], 'warehouse_ids' => $warehouse_ids));
                if($response_info['status'] == -1) {
                    $this->_return_json(array('status' => C('status.common.success'), 'msg' => '同步到stock表失败'));
                }
            }
        }
    }

    private function _set_redis_storage_buy_limit($product_info) {
        // 设置库存池
        if(intval($product_info['storage']) > 0) {
            $this->redisclient->hset($product_info['id'], 'storage',$product_info['storage']);
        }
        if(intval($product_info['buy_limit']) > 0) {
            $this->redisclient->hset($product_info['id'], 'buy_limit', $product_info['buy_limit']);
        }
    }

    /**
     * 列出商品调价页面的下拉列表选项
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function list_price_options() {
        $this->check_validation('product', 'list', '', FALSE);
        $data = $this->format_query('product/list_price_options', $_POST);
        $this->_return_json($data);
    }

    /**
     * 列出已调价商品列表
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function list_changed_prices() {
        $this->check_validation('product', 'list', '', FALSE);
        $data = $this->format_query('product/list_changed_prices', $_POST);
        $this->_return_json($data);
    }

    /**
     * 列出所有商品价格列表
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function list_prices() {
        $this->check_validation('product', 'list', '', FALSE);
        $data = $this->format_query('product/list_prices', $_POST);
        $this->_return_json($data);
    }

    /**
     * 更新商品价格
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function update_price() {
        $this->check_validation('product', 'list', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        $_POST['cur'] = $cur;
        $data = $this->format_query('product/update_price', $_POST);
        $this->_return_json($data);
    }

    /**
     * 同步所有修改的商品价格
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function sync_prices() {
        $this->check_validation('product', 'list', '', FALSE);
        // 获取所有要改价商品列表
        $product_prices = $this->format_query('product/list_changed_prices', ['itemsPerPage' => 'all']);
        if ($product_prices['status'] != C('status.req.success')) {
            $this->_return(false);
        }
        $product_prices = $product_prices['list'];

        // 创建快照
        $cur = $this->userauth->current(false);
        $product_ids = array_column($product_prices, 'product_id');
        $response = $this->format_query('product_snapshot/create_batch', ['user_id' => $cur['id'], 'product_ids' => $product_ids]);
        if ($response['status'] != C('status.req.success')) {
            $this->_return(false);
        }

        // 同步价格：修改价格&删除改价记录
        $response = $this->format_query('product/sync_prices', $_POST);
        $this->_return_json($response);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 日志详情
     */
    private function _log_detail() {
        $post_data = array(
            'edit_type' => C('workflow_log.edit_type.product'),
            'obj_id' => isset($_POST['id']) ? $_POST['id'] : 0
        );
        $response = $this->format_query('/workflow_log/info', $post_data);
        return isset($response['list']) ? $response['list'] : array();
    }
}

/* End of file product.php */
/* Location: :./application/controllers/product.php */
