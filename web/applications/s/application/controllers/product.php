<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 商品服务
 * @author: liaoxianwen@ymt360.com
 * @version: 2.0.0
 * @since: 2015-3-3
 * TODO 去掉老的，发布商品时也需要更新product_nums
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
                'MProperty',
                'MProduct_price',
            )
        );
        $this->units = C('unit');
        $this->product_status = C('product');
        $this->load->library(array('Cate_logic','product_lib', 'form_validation', 'product_price'));
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

        $normal_customer = C('customer.type.normal.value');
        $customer_type = empty($_POST['customer_type']) ? $normal_customer : $_POST['customer_type'];
        $visiable_arr = C('customer_visiable');
        if($customer_type == $normal_customer) {
            $visiable_condition = array($visiable_arr['all']['value'], $visiable_arr['normal']['value']);
        } else {
            $visiable_condition = array($visiable_arr['all']['value'], $visiable_arr['ka']['value']);
        }
       // 查询列表, visiable =3 位不可见 1为全部可见，2为部分可见
        $products = $this->MProduct->get_lists('*',array(
                'in' => array(
                    'category_id' => $category_ids,
                    'customer_visiable' => $visiable_condition,
                ),
                'location_id' => $_POST['location_id'],
                'customer_type' => C('customer.type.normal.value'),
                'status' => C('status.product.up'),
                'visiable !=' => C('visio.none'),
            ),
            array('updated_time' => 'desc'),
            array(),
            $offset,
            $page_size
        );
        $total = $this->MProduct->count(
            array(
                'in' => array(
                    'category_id' => $category_ids,
                    'customer_visiable' => $visiable_condition,
                ),
                'location_id' => $_POST['location_id'],
                'customer_type' => $customer_type,
                'status' => C('status.product.up'),
                'visiable !=' => C('visio.none')
            )
        );
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list'   => $products,
                'total'  => $total
            )
        );
    }

    /**
     * @description 通过sku_number获取商品
     */
    public function get_products_by_sku() {
        $return = array('status' => C('tips.code.op_success'), 'list' => []);
        $sku_numbers = $_POST['sku_number'];
        if(empty($sku_numbers) || !is_array($sku_numbers)) {
            $this->_return_json($return);
        }
        //$page_size = isset($_POST['page_size']) ? intval($_POST['page_size']) : 100;
        //$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        $customer_type = isset($_POST['customer_type']) ? $_POST['customer_type'] : 0;
        $customer_visiable = array(C('customer.customer_visiable.all'), $customer_type);
        $lists = $this->MProduct->get_lists('*',array(
                'in' => array(
                    'sku_number' => $sku_numbers,
                    'customer_visiable' => $customer_visiable
                ),
                'location_id' => $_POST['location_id'],

                'customer_type' => 1,
                'status' => C('status.product.up'),
                'is_active' => C('status.sku.active'),
                'visiable !=' => C('visio.none')
            ),
            array('updated_time' => 'desc')
        );
        if($lists) {
            $return['list'] = $lists;
        }
        $this->_return_json($return);
    }

   /**
     * @author: liaoxianwen@ymt360.com
     * @description 产品创建
     */
    public function create(){
        $format_data = $this->_format_data();
        extract($format_data);
        $product_id = $this->MProduct->create($product);
        // 设置catemap的数量
        $this->_update_catemap_product_nums($product);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'info' => array('id' => $product_id, 'sku_number' => $product['sku_number']),
                'msg' => '保存成功'
            )
        );
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 编辑后保存
     */
    public function save() {
        $format_data = $this->_format_data();
        extract($format_data);
        $this->MProduct->update_info($product, array('id' =>$_POST['id']));
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'msg' => '保存成功'
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
        $where = $this->_fill_where();
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

    private function _fill_where() {
        if(!empty($_POST['where'])) {
            $where = $_POST['where'];
        }
        // 区分是否为快照
        $where['is_active'] = C('status.product.up');
        return $where;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 创建商品组合数据
     */
    private function _format_data() {
        $req_time = $this->input->server('REQUEST_TIME');
        $sku_info = $this->MSku->get_one('category_id, sku_number, spec', array('sku_number' => $_POST['sku_number']));
        if(!$sku_info) {
            $this->_return_json(
                array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '您查找的货物不存在'
                )
            );
        }
        // 拼接属性
        if(isset($_POST['unit_name'])) {
            $unit_id = $this->product_lib->get_unit_id($_POST['unit_name']);
        } else {
            $unit_id = $this->units[0]['id'];
        }
        $close_unit_id = $unit_id;
        if(is_array($_POST['line_id']) && !empty($_POST['line_id'])) {
            $_POST['line_id'] = implode(',', $_POST['line_id']);
        }
        $product = array(
            'title'             => $_POST['title'],
            'category_id'       => $sku_info['category_id'],
            'adv_words'         => $_POST['adv_words'],
            'location_id'       => $_POST['location_id'],
            'customer_type'     => $_POST['customer_type'],
            'collect_type'      => $_POST['collect_type'],
            'price'             => $_POST['price'],
            'market_price'      => $_POST['market_price'],
            'single_price'      => $_POST['price'],
            'storage'           => $_POST['storage'],
            'buy_limit'         => $_POST['buy_limit'],
            'line_id'           => $_POST['line_id'],
            'visiable'          => $_POST['visiable'],
            'is_round'          => $_POST['is_round'],
            'unit_id'           => $unit_id,
            'close_unit'        => $close_unit_id,
            'spec'              => $sku_info['spec'],
            'status'            => $_POST['status'],
            'created_time'      => $req_time,
            'updated_time'      => $req_time,
            'sku_number'        => $sku_info['sku_number'],
            // 客户类型的可见性
            'customer_visiable' => isset($_POST['customer_visiable']) ? $_POST['customer_visiable'] : C('customer_visiable.all.value')
        );
        return array(
            'product' => $product
        );
    }
    /**
     * @author: liaoxianwen@dachuwang.com
     * @description
     */
    public function set_status() {
        $data = array(
            'status' => $_POST['status']
        );
        if(isset($_POST['is_active'])) {
            $data['is_active'] = $_POST['is_active'];
        }
        if(!empty($_POST['updated_time'])) {
            $data['updated_time'] = $_POST['updated_time'];
        }
        $this->MProduct->update_info($data, $_POST['where']);
        if(isset($_POST['where']['id'])) {
            $product = $this->MProduct->get_one('id, status, category_id', $_POST['where']['id']);
            $this->_update_catemap_product_nums($product);
        }
        $this->_return_json(
            array(
                'status'    => C('tips.code.op_success')
            )
        );

    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 更新分类映射表里的商品数量
     */
    private function _update_catemap_product_nums($product) {
        $incr = empty($_POST['status']) ? -1 : 1;
        $info = $this->MCategory->get_one('path', array('id' => $product['category_id']));
        $cate_ids = explode('.', trim($info['path'], '.'));
        $customer_condition = [];
        // 根据客户可见性来设置customer_type
        if($product['customer_visiable'] == 1) {
            // set customer_type
            $customer_type = C('customer.type.normal.value');
            $customer_condition['customer_type'] = $customer_type;
        } else if($product['customer_visiable'] == 2) {
            $customer_type = C('customer.type.KA.value');
            $customer_condition['customer_type'] = $customer_type;
        }
        // 分类映射数据取值
        $catemaps = $this->MCategory_map->get_lists(
            'id, product_nums, origin_id',
            array_merge(
                array(
                    'location_id' => $product['location_id'],
                    'in' => array(
                        'origin_id' => $cate_ids
                    ),
                ), $customer_condition
            )
        );
        // 重组后的映射数组
        $new_catemap = [];
        // 根据客户类型来区分，更新数据
        foreach($catemaps as &$cate) {
            if(!is_bool(strpos($info['path'], $cate['origin_id']))) {
                $cate['product_nums'] = $cate['product_nums'] + $incr;
            }
        }
        // 批量更新
        $this->db->update_batch('t_category_map', $catemaps, 'id');
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
                $normal_customer = C('customer.type.normal.value');
                $customer_type = empty($_POST['customer_type']) ? $normal_customer : $_POST['customer_type'];
                $visiable_arr = C('customer_visiable');
                if($customer_type == $normal_customer) {
                    $visiable_condition = array($visiable_arr['all']['value'], $visiable_arr['normal']['value']);
                } else {
                    $visiable_condition = array($visiable_arr['all']['value'], $visiable_arr['ka']['value']);
                }

                $where = array(
                    'in' => array(
                        'sku_number' => $sku_numbers,
                        'customer_visiable' => $visiable_condition,
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

    /**
     * @author: liaoxianwen@ymt360.com
     * @description api层检测提交订单的products接口
     */
    public function check_valid_order_products() {
        $where = array();
        if(!empty($_POST['where'])) {
            $where = $_POST['where'];
        }
        $total =  $this->MProduct->count($where);
        $data = $this->MProduct->get_lists(
            array(),
            $where,
            array('id' => 'DESC'),
            array()
        );
        $data = $this->product_lib->format_product_data($data);
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list' => $data
            )
        );
    }

    /**
     * 列出商品调价页面的下拉列表选项
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function list_price_options() {
        $categories = $this->MCategory->get_lists('id, name', ['upid' => C('status.common.top'), 'status' => C('status.common.success')]);
        $cities = $this->MLocation->get_lists('id, name', ['upid' => C('status.common.top'), 'status' => C('status.common.success')]);

        $this->_return_json(
            [
                'status'     => C('status.req.success'),
                'categories' => $categories,
                'cities'     => $cities,
            ]
        );
    }

    /**
     * 列出已调价商品列表
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function list_changed_prices() {
        // 参数解析&数据处理
        $page = $this->get_page() ;
        $where = array () ;
        $where['status'] = C('status.common.success');
        if (!empty($_POST['locationId'])) {
            $where['location_id'] = $_POST['locationId'];
        }
        if (!empty($_POST['categoryId'])) {
            $child_ids = $this->MCategory->get_children_ids([$_POST['categoryId']]);
            $child_ids = array_column($child_ids, 'id');
            $where['in']['category_id'] = $child_ids;
        }
        if (!empty($_POST['skuNumber'])) {
            $where['sku_number'] = $_POST['skuNumber'];
        }
        $order = ['created_time' => 'desc'];
        $list = $this->MProduct_price->get_lists('*', $where, $order, [], $page['offset'], $page['page_size']);
        $total = $this->MProduct_price->count($where);
        $list = $this->_format_list($list);
        $list = $this->_set_product_price($list);
        $list = $this->_format_price($list);

        $this->_return_json(
            [
                'status' => C('status.req.success'),
                'list'   => $list,
                'total'  => $total,
            ]
        );
    }

    /**
     * 列出所有商品价格列表
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function list_prices() {
        // 参数解析&数据处理
        $page = $this->get_page() ;
        // 查询出普通客户的价格
        $where = array () ;
        $where['status'] = C('status.common.success');
        $where['customer_type'] = C('customer.type.normal.value');
        if (!empty($_POST['locationId'])) {
            $where['location_id'] = $_POST['locationId'];
        }
        if (!empty($_POST['categoryId'])) {
            $child_ids = $this->MCategory->get_children_ids([$_POST['categoryId']]);
            $child_ids = array_column($child_ids, 'id');
            $where['in']['category_id'] = $child_ids;
        }
        if (!empty($_POST['skuNumber'])) {
            $where['sku_number'] = $_POST['skuNumber'];
        }
        $order = ['created_time' => 'desc'];
        $list = $this->MProduct->get_lists('id as product_id, location_id, sku_number, category_id, title, price, updated_time', $where, $order, [], $page['offset'], $page['page_size']) ;
        $total = $this->MProduct->count($where);
        $list = $this->_format_list($list);
        $list = $this->_set_product_changed_price($list);
        $list = $this->_format_price($list);

        // 返回结果
        $this->_return_json(
            [
                'status' => C('status.req.success'),
                'list'   => $list,
                'total'  => $total
            ]
        );
    }

    /**
     * 更新商品价格
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function update_price() {
        // 表单校验
        $this->form_validation->set_rules('productId', '商品ID', 'required');
        $this->validate_form();

        $cur = $_POST['cur'];
        // 删除价格表中原有记录
        $this->MProduct_price->false_delete(['product_id' => $_POST['productId']]);
        $product = $this->MProduct->get_one('*', ['id' => $_POST['productId'], 'status' => C('status.common.success')]);
        if (empty($product)) {
            $this->_return(false, '商品信息不存在或已下架');
        }
        $data = [
            'product_id'    => $product['id'],
            'product_name'  => $product['title'],
            'sku_number'    => $product['sku_number'],
            'category_id'   => $product['category_id'],
            'location_id'   => $product['location_id'],
            'dest_price'    => isset($_POST['destPrice']) ? $_POST['destPrice'] * 100 : 0,
            'created_time'  => $this->input->server("REQUEST_TIME"),
            'updated_time'  => $this->input->server("REQUEST_TIME"),
            'operator'      => $cur['name'],
            'operator_id'   => $cur['id'],
            'status'        => C('status.common.success'),
        ];

        $this->MProduct_price->create($data);

        $this->_return_json(
            [
                'status' => C('status.req.success'),
                'msg'    => C('tips.msg.op_success')
            ]
        );
    }

    /**
     * 同步所有修改的商品价格
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    public function sync_prices() {
        $list = $this->MProduct_price->get_lists('*', ['status' => C('status.common.success')]);
        foreach ($list as $item) {
            // 修改商品普通客户价格
            if (!empty($item['dest_price'])) {
                $this->_sync_product_price($item);
            }
        }

        $this->_return_json(
            [
                'status' => C('status.req.success'),
                'msg'    => C('tips.msg.op_success')
            ]
        );
    }


    /**
     * 格式化列表数据,设置分类名称和所属城市名称
     * @author yugang@dachuwang.com
     * @since 2015-07-09
     */
    private function _format_list($list) {
        $result = [];
        if (empty($list)) {
            return $result;
        }
        $location_ids = array_column($list, 'location_id');
        $location_list = $this->MLocation->get_lists('id, name', ['in' => ['id' => $location_ids]]);
        $location_dict = array_column($location_list, 'name', 'id');
        $top_categories = $this->MCategory->get_lists('id, name, path', ['upid' => C('status.common.top')]);
        $category_ids = array_column($list, 'category_id');
        $categories = $this->MCategory->get_lists('id, name, path', ['in' => ['id' => $category_ids]]);
        $category_dict = [];
        foreach ($categories as $cate) {
            foreach ($top_categories as $top_cate) {
                if (strpos($cate['path'],  $top_cate['path']) !== FALSE) {
                    $category_dict[$cate['id']] = $top_cate;
                    break;
                }
            }
        }
        foreach ($list as $item) {
            $item['top_category_name'] = isset($category_dict[$item['category_id']]) ? $category_dict[$item['category_id']]['name'] : '';
            $item['location_name'] = isset($location_dict[$item['location_id']]) ? $location_dict[$item['location_id']] : '';
            $item['updated_time'] = date('Y-m-d H:i:s', $item['updated_time']);
            $result[] = $item;
        }

        return $result;
    }

    /**
     * 设置产品的普通客户价格
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    private function _set_product_price($list) {
        $result = [];
        if (empty($list)) {
            return $result;
        }
        $product_ids = array_column($list, 'product_id');
        $product_list = $this->MProduct->get_lists('*', ['in' => ['id' => $product_ids]]);
        $price_dict = array_column($product_list, 'price', 'id');
        foreach ($list as $item) {
            $key = $item['product_id'];
            $item['price'] = isset($price_dict[$key]) ? $price_dict[$key] : 0;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * 设置产品的修改后的普通客户价格
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    private function _set_product_changed_price($list) {
        $result = [];
        if (empty($list)) {
            return $result;
        }
        $product_ids = array_column($list, 'product_id');
        $price_list = $this->MProduct_price->get_lists('*', ['in' => ['product_id' => $product_ids]]);
        $price_dict = array_column($price_list, NULL, 'product_id');
        foreach ($list as $item) {
            $key = $item['product_id'];
            $item['dest_price'] = isset($price_dict[$key]) ? $price_dict[$key]['dest_price'] : $item['price'];
            $item['operator'] = isset($price_dict[$key]) ? $price_dict[$key]['operator'] : '';
            $result[] = $item;
        }

        return $result;
    }

    /**
     * 设置产品的修改后的普通客户价格
     * @author yugang@dachuwang.com
     * @since 2015-07-08
     */
    private function _format_price($list) {
        $result = [];

        foreach ($list as $item) {
            $item['price'] = isset($item['price']) ? $item['price'] / 100 : 0;
            $item['dest_price'] = isset($item['dest_price']) ? $item['dest_price'] / 100 : 0;
            $result[] = $item;
        }

        return $result;
    }

    /**
     * 同步修改商品价格
     * @author yugang@dachuwang.com
     * @since 2015-07-09
     */
    private function _sync_product_price($item) {
        $product = $this->MProduct->get_one('*', ['id' => $item['product_id'], 'status' => C('status.common.success')]);
        if (empty($product)) {
            return;
        }

        // 更新商品价格
        $this->MProduct->update_info(['price' => $item['dest_price'], 'single_price' => $item['dest_price']], ['id' => $product['id']]);
        // 删除价格记录
        $this->MProduct_price->false_delete(['id' => $item['id']]);
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
