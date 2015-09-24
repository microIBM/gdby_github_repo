<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 客户基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-04
 */
class Customer extends MY_Controller {

    protected $_salt  = NULL;
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MLocation',
                'MDepartment',
                'MCustomer',
                'MRole',
                'MPhone',
                'MOrder',
                'MSuborder',
                'MProduct',
                'MCategory',
                'MSms_log',
                'MLine',
                'MPotential_customer',
                'MCustomer_image',
                'MWorkflow_log'
            )
        );
        $this->load->library(
            array(
                'form_validation',
                'filter_orders',
                'location',
            )
        );
        $this->_wait_status_arr = array(
            C('order.status.confirmed.code'),
            C('order.status.wave_executed.code'),
            C('order.status.picking.code'),
            C('order.status.picked.code'),
            C('order.status.checked.code'),
            C('order.status.allocated.code'),
            C('order.status.delivering.code'),
            C('order.status.loading.code'),
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description
     */
    public function sub_accounts() {
        $sub_accounts = $this->_get_sub_accounts();
        $response = array(
            'status' => C('tips.code.op_success'),
            'list'  => $sub_accounts
        );
        $this->_return_json($response);
    }

    public function get_parent_info_by_mobile() {
        if(empty($_POST['mobile'])) {
            $parent_info = [];
        } else {
            $parent_mobile = $_POST['mobile'];
            //判断是否为母账号
            $parent_info = $this->MCustomer->get_one(['id', 'billing_cycle'], ['mobile' => $parent_mobile]);
        }
        $response = array(
            'status' => C('tips.code.op_success'),
            'info'  => $parent_info
        );
        $this->_return_json($response);
    }
    private function _get_sub_accounts() {
        if(empty($_POST['id'])) {
            return [];
        }
        $parent_id = $_POST['id'];
        //判断是否为母账号
        $parent_info = $this->MCustomer->get_one(['customer_type','account_type','mobile'], ['id' => $parent_id]);
        $parent_type = C('customer.account_type.parent.value');
        if(!$parent_info || intval($parent_info['account_type']) !== $parent_type) {
            return [];
        }
        $sub_lists = $this->MCustomer->get_lists(
            ['id', 'shop_name', 'address', 'mobile', 'name', 'username','shop_name'],
            [
                'parent_mobile' => $parent_info['mobile'],
                'customer_type' => $parent_info['customer_type'],
                'account_type'  => C('customer.account_type.child.value')
            ]
        );
        return $sub_lists;
    }
   /**
     * 查看客户
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function view() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->MCustomer->get_one('*', ['id' => $_POST['id']]);
        $data = $this->_format_list([$data]);
        $data = $data[0];

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }


    /**
     * 客户列表
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function lists() {
        // 参数解析&数据处理
        $page = $this->get_page();
        $where = array();
        // 所属城市
        if(!empty($_POST['provinceId'])) {
            $where['province_id'] = $_POST['provinceId'];
        }
        if(!empty($_POST['invite_id'])){
            $where['invite_id'] = $_POST['invite_id'];
        }
        if(!empty($_POST['not_invite_id'])){
            $where['not_in']['invite_id'] = $_POST['not_invite_id'];
        }
        if(!empty($_POST['am_id'])){
            $where['am_id'] = $_POST['am_id'];
        }
        $where['status !='] = C('status.common.del');
        // $where['status'] = C('status.common.success');
        if(isset($_POST['status']) && 'normal' == $_POST['status']) {
            $where['status >'] = C('status.common.del');
        } elseif (!empty($_POST['status']) && 'unallocated' == $_POST['status']) {
            $where['status'] = C('customer.status.unallocated.code');
        } elseif (!empty($_POST['status']) && 'all' != $_POST['status']) {
            $where['status'] = $_POST['status'];
        }
        if (!empty($_POST['status_list']) && is_array($_POST['status_list'])) {
            $where['in']['status'] = $_POST['status_list'];
        }
        $customer_types = array_column(array_values(C('customer.type')), 'value');
        // 根据客户类型筛选
        if (!empty($_POST['customer_type']) && in_array($_POST['customer_type'], $customer_types)) {
            $where['customer_type'] = $_POST['customer_type'];
        }
        // 未审核客户
        if (!empty($_POST['customer_type']) && $_POST['customer_type'] == C('customer.list_type.TO_AUDIT.value')) {
            $where['is_active'] = C('customer.status.invalid.code');
        }

        // 是否已分配配送线路
        if(isset($_POST['line_status'])) {
            if($_POST['line_status'] == 'toAllot') {
                $where['line_id'] = 0;
            }else if($_POST['line_status'] == 'allot') {
                $where['line_id >'] = 0;
            }
        }
        if(!empty($_POST['lineId'])) {
            $where['line_id'] = $_POST['lineId'];
        }
        if(!empty($_POST['startTime'])) {
            $where['created_time >='] = $_POST['startTime'] / 1000;
        }
        if(!empty($_POST['endTime'])) {
            $where['created_time <='] = $_POST['endTime'] / 1000 + 86400;
        }
        if(!empty($_POST['searchValue']) && !empty($_POST['searchKey'])) {
            $where['like'] = array($_POST['searchKey'] => $_POST['searchValue']);
        }
        if (! empty($_POST['key'])) {
            // 如果输入关键词为数字，则匹配手机号
            if (preg_match("/^\d{1,11}$/", $_POST['key'])) {
                $where['like'] = array (
                    'mobile' => $_POST['key']
                ) ;
            } else {
                $where['like'] = array (
                    'shop_name' => $_POST['key']
                ) ;
            }
        }
        if(!empty($_POST['title'])){
            // 如果输入关键词为数字，则匹配手机号
            if (preg_match("/^\d{1,11}$/", $_POST['title'])) {
                $where['like'] = array (
                    'mobile' => $_POST['title']
                ) ;
            } else {
                $where['like'] = array (
                    'shop_name' => $_POST['title']
                ) ;
            }
        }
        // 经纬度范围查询
        if(!empty($_POST['longtitude']) && !empty($_POST['latitude'])) {
            $range = isset($_POST['range']) ? $_POST['range'] : C('customer.cloudmap.search_range');
            $squares = $this->location->get_square_points($_POST['longtitude'], $_POST['latitude'], $range);
            $where['lng >'] = $squares['left-top']['lng'];
            $where['lng <'] = $squares['right-bottom']['lng'];
            $where['lat >'] = $squares['right-bottom']['lat'];
            $where['lat <'] = $squares['left-top']['lat'];
        }
        if(!empty($_POST['customer_ids']) && is_array($_POST['customer_ids'])) {
            $where['in']['id'] = $_POST['customer_ids'];
        }
        //暂时不处理前端关于排序的请求
        $order = array('created_time' => 'desc');
        //处理筛选
        if(!empty($_POST['conditions']) && is_array($_POST['conditions']) && !empty($_POST['conditions']['sift']) && is_array($_POST['conditions']['sift'])) {
            $temp = &$_POST['conditions']['sift'];
            if(!empty($temp['line'])) {
                $where['line_id'] = intval($temp['line']);
            }
            if(!empty($temp['province'])) {
                $where['province_id'] = intval($temp['province']);
            }
            if(!empty($temp['dimensions'])) {
                $where['dimensions'] = json_encode($temp['dimensions']);
            }
        }
        $arr = $this->_get_lists($where, $order, $page);

        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * @param id 母账号id
     * @description 获取子账号地址列表
     */
    public function sub_account_address() {
        $return = array(
            'list' => []
        );
        if(empty($_POST['id'])) {
            $this->_return_json($return);
        }
        $parent_id = $_POST['id'];
        //是否是母账号
        $parent_info = $this->MCustomer->get_one(['customer_type','account_type','mobile'], ['id' => $parent_id]);
        //parent:1, child:2
        $account_types = C('customer.account_type');
        $parent_type = $account_types['parent']['value'];
        if(!$parent_info || $parent_info['account_type'] != $parent_type) {
            $this->_return_json($return);
        }
        $where = array(
            'parent_mobile' => $parent_info['mobile'],
            'customer_type' => $parent_info['customer_type'],
            'account_type'  => $account_types['child']['value']
        );
        $total = $this->MCustomer->count($where);
        $page = $this->get_page();
        $sub_lists = $this->MCustomer->get_lists(
            ['id', 'address', 'mobile', 'name', 'username', 'shop_name'],
            $where,
            [],
            [],
            $page['offset'],
            $page['page_size']
        );
        if(!empty($sub_lists)) {
            $return['list'] = $sub_lists;
            $return['total'] = $total;
        }
        $this->_return_json($return);
    }

    private function _get_ordered_customer($invite_id, $type) {
        //获取某个BD下的所有客户id
        $arr = $this->MCustomer->get_lists(['id'],['invite_id'=>$invite_id]);
        $cids = [];
        foreach($arr as $v) {
            $cids[] = $v['id'];
        }
        //获得已下单客户的id
        $arr = $this->MOrder->get_lists(['distinct(user_id) as uid'],['in'=>['user_id'=>$cids]]);
        $orderids = [];
        foreach($arr as $v) {
            $orderids[] = $v['uid'];
        }
        //如果查询的是未下单客户
        if(intval($type) === 0) {
            $arr = [];
            foreach($cids as $v) {
                if(!in_array($v, $orderids)) {
                    $arr[] = $v;
                }
            }
            return $arr;
        }
        return $orderids;
    }

    /*
     * @description 筛选地区
     * @return array|false 成功返回where条件，失败返回false
     */
    private function filterDistrict() {
        if(isset($_POST['conditions']['sift']['province'])) {
            return ['province_id' => $_POST['conditions']['sift']['province']];
        }
        return false;
    }

    /*
     * @description 筛选配送线路
     * @return array|false 成功返回where条件，失败返回false
     */
    private function filterLine() {
        if(isset($_POST['conditions']['sift']['line'])) {
            return ['line_id' => $_POST['conditions']['sift']['line']];
        }
        return false;
    }

    /*
     * @description 筛选客户规模
     * @return array|false
     */
    private function filterDimensions() {
        if(isset($_POST['conditions']['sift']['dimensions'])) {
            return ['dimensions' => $_POST['conditions']['sift']['dimensions']];
        }
        return false;
    }

    /*
     * @description 筛选餐饮类别
     * @return array|false
     */
    private function filterShopType() {
        if(isset($_POST['conditions']['sift']['shop_type'])) {
            return ['shop_type' => $_POST['conditions']['sift']['shop_type']];
        }
        return false;
    }

    /*
     * @description 筛选KA or notKA
     * @return array|false
     */
    private function filterCustomerType() {
        if(isset($_POST['conditions']['sift']['customer_type'])) {
            return ['customer_type' => $_POST['conditions']['sift']['customer_type']];
        }
        return false;
    }

    /*
     * @description 筛选搜索关键字
     * @return arrya|false
     */
    private function filterSearchKey() {
        $where = [];
        if (! empty($_POST['key'])) {
            // 如果输入关键词为数字，则匹配手机号
            if (preg_match("/^\d{1,11}$/", $_POST['key'])) {
                $where['like'] = array (
                    'mobile' => $_POST['key']
                ) ;
            } else {
                $where['like'] = array (
                    'shop_name' => $_POST['key']
                ) ;
            }
        }
        if(!empty($where)) {
            return $where;
        }
        return false;
    }

    /*
     * @description 根据invite_id筛选客户
     * @return array|false
     */
    private function filterInviteId() {
        if(isset($_POST['invite_id'])) {
            return ['invite_id' => $_POST['invite_id']];
        }
        return false;
    }

    /*
     * @description 排序字段  created_time  排序时间降序, latest_ordered_time 最后下单时间降序
     * @return array|false
     */
    private function filterOrderField() {
        if(isset($_POST['order_field'])) {
            if  (strval($_POST['order_field'])  ==  'latest_ordered_time' ){
                return ['latest_ordered_time' => 'desc'];
            }
        }
        return ['created_time' => 'desc'];
    }

    /*
     * @description 筛选是否下单
     * @param array $where | 目前已有的$where条件
     * @return array|false
     */
    private function filterOrderType($where) {
        if(isset($_POST['conditions']['sift']['order_type'])) {
            //根据现有where条件筛选出客户id
            $ids = $this->getCustomerIdsByWhere($where);
            if(!$ids) {
                return false;
            }
            //筛选出下过单的客户id
            $ordered_ids = $this->getOrderedCustomerIdsFromIds($ids);
            $condition = [];
            $order_type = $_POST['conditions']['sift']['order_type'];
            //如果筛选条件是已下单
            if($order_type == C('customer.order_record.with.code')) {
                if(empty($ordered_ids)) {
                    //查询id为0的客户相当于强制where条件返回空集
                    $condition['id'] = 0;
                } else {
                    $condition['in'] = ['id' => $ordered_ids];
                }
            } else {
            //如果筛选条件是未下单
                if(!empty($ordered_ids)) {
                    $condition['not_in'] = ['id' => $ordered_ids];
                } else {
                    return false;
                }
            }
            return $condition;
        }
        return false;
    }

    /*
     * @description 合并数组
     * @param array $where, array|false $condition, integer
     * @return array
     */
    private function mergeWhere($where, $condition, $type=1) {
        if(!is_array($condition)) {
            return $where;
        }
        if($type === 1) {
            return array_merge_recursive($where, $condition);
        }
        return array_merge($where, $condition);
    }

    /*
     * @description 获取$ids数组中下过订单的部分客户id
     * @param array $ids | 客户id列表
     * @return array|false
     */
    private function getOrderedCustomerIdsFromIds($ids) {
        $order_where = [
            'status !=' => C('order.status.closed.code'),
            'in' => ['user_id' => $ids]
        ];
        $time_arr = $this->getStartAndEnd();
        if($time_arr) {
            $order_where['created_time >'] = $time_arr['start'];
            $order_where['created_time <='] = $time_arr['end'];
        }
        $ordered_cus = $this->MOrder->get_lists(['distinct user_id'], $order_where);
        if(count($ordered_cus) <=0) {
            return false;
        }
        $sub_ids = [];
        foreach($ordered_cus as $v) {
            $sub_ids[] = intval($v['user_id']);
        }
        return $sub_ids;
    }

    /*
     * @description 获取当前条件下能查询的客户id列表
     * @param array $where | 已拼装好的where条件
     * @return array|false
     */
    private function getCustomerIdsByWhere($where) {
        $ids = [];
        $result = $this->MCustomer->get_lists(['id'], $where);
        if(count($result) <=0) {
            return false;
        }
        foreach($result as $v) {
            $ids[] = intval($v['id']);
        }
        return $ids;
    }

    public function register_lists() {
        $page = $this->get_page();
        $where = [
            'not_in' => [
                'status' => [C('customer.status.disabled.code'), C('customer.status.invalid.code')]
            ]
        ];
        $order = $this->filterOrderField();
        //invite_id
        $where = $this->mergeWhere($where, $this->filterInviteId());
        //地区
        $where = $this->mergeWhere($where, $this->filterDistrict());
        $totalCustomerNumber = $this->MCustomer->count($where);
        //线路
        $where = $this->mergeWhere($where, $this->filterLine());
        //客户规模
        $where = $this->mergeWhere($where, $this->filterDimensions());
        //餐饮类别
        $where = $this->mergeWhere($where, $this->filterShopType());
        //KA or Not
        $where = $this->mergeWhere($where, $this->filterCustomerType());
        //用户输入搜索条件
        $where = $this->mergeWhere($where, $this->filterSearchKey());
        //客户是否下单(存在归属于某客户的、状态不是关闭的订单)
        $where = $this->mergeWhere($where, $this->filterOrderType($where));
        $arr = $this->_get_lists($where, $order, $page, $totalCustomerNumber);
        // 返回结果
        $this->_return_json($arr);
    }

    private function _add_prefix($where, $prefix) {
        $arr = [];
        foreach($where as $key => $v) {
            $arr[$prefix.$key] = $v;
        }
        return $arr;
    }

    /**
     * BD的新注册客户
     * @author yugang@dachuwang.com
     * @since 2015-04-28
     */
    public function new_register_lists() {
        // 参数解析&数据处理
        $page = $this->get_page();
        $where = array();
        $where['status'] = C('customer.status.new.code');
        if(isset($_POST['invite_id'])){
            $where['invite_id'] = $_POST['invite_id'];
        }
        if (! empty($_POST['key'])) {
            // 如果输入关键词为数字，则匹配手机号
            if (preg_match("/^\d{1,11}$/", $_POST['key'])) {
                $where['like'] = array (
                    'mobile' => $_POST['key']
                ) ;
            } else {
                $where['like'] = array (
                    'shop_name' => $_POST['key']
                ) ;
            }
        }

        $order = array('created_time' => 'desc');
        //处理筛选
        if(!empty($_POST['conditions']) && is_array($_POST['conditions']) && !empty($_POST['conditions']['sift']) && is_array($_POST['conditions']['sift'])) {
            $temp = &$_POST['conditions']['sift'];
            if(!empty($temp['line'])) {
                $where['line_id'] = intval($temp['line']);
            }
            if(!empty($temp['province'])) {
                $where['province_id'] = intval($temp['province']);
            }
            if(!empty($temp['dimensions'])) {
                $where['dimensions'] = $temp['dimensions'];
            }
            if(!empty($temp['shop_type'])) {
                $where['shop_type'] = $temp['shop_type'];
            }
        }

        $arr = $this->_get_lists($where, $order, $page);
        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * BD的已经下单但未完成客户,以及待分配客户
     * @author yugang@dachuwang.com
     * @since 2015-04-28
     */
    public function undone_lists() {
        // 参数解析&数据处理
        $page = $this->get_page();
        $where = array();
        $where['in'] = array('status' => array(C('customer.status.undone.code'), C('customer.status.unallocated.code')));
        if(isset($_POST['invite_id'])){
            $where['invite_id'] = $_POST['invite_id'];
        }
        if (! empty($_POST['key'])) {
            // 如果输入关键词为数字，则匹配手机号
            if (preg_match("/^\d{1,11}$/", $_POST['key'])) {
                $where['like'] = array (
                    'mobile' => $_POST['key']
                ) ;
            } else {
                $where['like'] = array (
                    'shop_name' => $_POST['key']
                ) ;
            }
        }

        $order = array('created_time' => 'desc');

        if(!empty($_POST['conditions']) && is_array($_POST['conditions']) && !empty($_POST['conditions']['sift']) && is_array($_POST['conditions']['sift'])) {
            $temp = &$_POST['conditions']['sift'];
            if(!empty($temp['line'])) {
                $where['line_id'] = intval($temp['line']);
            }
            if(!empty($temp['province'])) {
                $where['province_id'] = intval($temp['province']);
            }
            if(!empty($temp['dimensions'])) {
                $where['dimensions'] = $temp['dimensions'];
            }
            if(!empty($temp['shop_type'])) {
                $where['shop_type'] = $temp['shop_type'];
            }
        }
        $arr = $this->_get_lists($where, $order, $page);

        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * AM的用户列表
     * @author yugang@dachuwang.com
     * @since 2015-04-28
     */
    public function after_sale_lists() {
        // 参数解析&数据处理
        $page = $this->get_page();
        $where = array();
        $where['status'] = C('customer.status.allocated.code');
        if(isset($_POST['invite_id'])){
            $where['am_id'] = $_POST['invite_id'];
        }
        if (! empty($_POST['key'])) {
            // 如果输入关键词为数字，则匹配手机号
            if (preg_match("/^\d{1,11}$/", $_POST['key'])) {
                $where['like'] = array (
                    'mobile' => $_POST['key']
                ) ;
            } else {
                $where['like'] = array (
                    'shop_name' => $_POST['key']
                ) ;
            }
        }

        $order = array('created_time' => 'desc');

        if(!empty($_POST['conditions']) && is_array($_POST['conditions']) && !empty($_POST['conditions']['sift']) && is_array($_POST['conditions']['sift'])) {
            $temp = &$_POST['conditions']['sift'];
            if(!empty($temp['line'])) {
                $where['line_id'] = intval($temp['line']);
            }
            if(!empty($temp['province'])) {
                $where['province_id'] = intval($temp['province']);
            }
            if(!empty($temp['dimensions'])) {
                $where['dimensions'] = $temp['dimensions'];
            }
            if(!empty($temp['shop_type'])) {
                $where['shop_type'] = $temp['shop_type'];
            }
        }
        $arr = $this->_get_lists($where, $order, $page);

        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * 根据条件返回客户列表
     * @author yugang@dachuwang.com
     * @since 2015-04-28
     */
    private function _get_lists($where, $order, $page, $totalCustomerNumber = 0) {
        $total = $this->MCustomer->count($where);
        if($page['offset'] >= $total){
            return [
                'status' => C('status.req.success'),
                'list' => [],
                'total' => $total,
                'total_number' => $totalCustomerNumber
            ];
        }
        // 如果传递了需要返回的字段，则只返回对应的字段且不对数据格式化
        $fields = !empty($_POST['fields']) ? $_POST['fields'] : '*';
        $list = $this->MCustomer->get_lists($fields, $where, $order, array(), $page['offset'], $page['page_size']);
        if ($fields == '*') {
            $list = $this->_format_list($list);
        }
        $arr = array(
            'status' => C('status.req.success'),
            'list'   => $list,
            'total'  => $total,
            'total_number' => $totalCustomerNumber
        );

        return $arr;
    }

    /**
     * @description 检测请求的必须数据是否存在
     * @author liudeen@dachuwang.com
     * @add 2015-03-23
     */
    private function _checkNecessary($list) {
        foreach($list as $value) {
            if(!isset($_POST[$value])) {
                return FALSE;
            }
        }
        return TRUE;
    }

    /**
     * @description 设置排序
     * @author liudeen@dachuwang.com
     * @add 2015-03-23
     */
    private function _set_order_by($order, $model) {
        if(is_array($order)) {
            $order_array('desc','asc');
            foreach($order as $key => $value) {
                if(in_array($value, $order_array)) {
                    $this->$model->order_by($key, $value);
                }
            }
        }
    }

    /**
     * @description 封装错误
     * @author liudeen@dachuwang.com
     * @add 2015-03-23
     *
     */
    private function _assembleError($status, $msg) {
        $res = array(
            'status' => $status,
            'msg' => $msg
        );
        return $res;
    }

    /**
     * @description 封装输出
     * @author liudeen@dachuwang.com
     * @add 2015-03-23
     */
    private function _assembleResult($arr, $status, $msg) {
        $res = array(
            'status' => $status,
            'list' => $arr,
            'msg' => $msg
        );
        return $res;
    }

    /**
     * 客户列表
     * @author liudeen@dachuwang.com
     * @modify 2015-03-23
     */
    public function list_group() {
        // 参数解析&数据处理
        if(!$this->_checkNecessary(array('user_id'))) {
            $this->_return_json($this->_assembleError(C('status.req.failed'), 'lack of necessary param'));
        }
        $user_id = $this->input->post('user_id', TRUE);
        $cur = $this->MUser->get_user_info(array('id'=>$user_id));
        $dept_list = $this->MDepartment->get_children($cur['dept_id']);
        $dept_list[] = $cur['dept_id'];
        $is_bdm = in_array($cur['role_id'], [C('user.saleuser.BDM.type'), C('user.saleuser.CM.type')]);
        $mygroup = array();
        $bd_arr = array(C('user.saleuser.BD.type'), C('user.saleuser.BDM.type'));
        $am_arr = array(C('user.saleuser.AM.type'), C('user.saleuser.SAM.type'));

        if($is_bdm) {
            $where_common['in'] = array('status' => array(C('customer.status.new.code'), C('customer.status.undone.code')));
            $db_group = $this->MUser->get_lists('id, name', array('in' => array('role_id' => $bd_arr, 'dept_id' => $dept_list), 'id !=' => $cur['id'], 'status >' => C('status.user.del')));
        }else{
            $where_common['in'] = array('status' => array(C('customer.status.allocated.code')));
            $db_group = $this->MUser->get_lists('id, name', array('in' => array('role_id' => $am_arr, 'dept_id' => $dept_list), 'id !=' => $cur['id'], 'status >' => C('status.user.del')));
        }
        foreach($db_group as $value) {
            $where = array();
            $where = array_merge($where, $where_common);
            $where['invite_id'] = $value['id'];
            $mygroup[] = array(
                'name' => $value['name'],
                'uid' => $value['id'],
                'count' => $this->MCustomer->count($where),
            );
        }

        $where = array();
        $where = array_merge($where, $where_common);
        $where['invite_id'] = $cur['id'];
        $my = array(
            'uid' => $user_id,
            'name' => $cur['name'],
            'count' => $this->MCustomer->count($where)
        );
        $this->_return_json($this->_assembleResult(array('list'=>$mygroup,'customer'=>$my), C('status.req.success'), '请求成功'));
        //SELECT name,id,count(*)
    }

    /**
     * 添加客户页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function create_input() {
        // 数据处理
        $where = array('status' => 1);
        $province_list = $this->MLocation->get_lists('*', array('upid' => '0'));
        $line_list = $this->MLine->get_lists('*', array('status' => C('status.common.success')));
        // 商家的类别
        $shop_type = array_values(C('customer_type.top'));
        // 客户类别
        $types = array_values(C('customer.type'));
        $dimensions = array_values(C('customer.dimension'));
        $directions = array_values(C('customer.direction'));
        $account_types = array_values(C('customer.account_type'));
        $estimated = array_values(C('customer.estimated'));
        $directions = array_values(C('customer.direction'));

        // 返回结果
        $this->_return_json(
            array(
                'status'         => C('status.req.success'),
                'provinces'      => $province_list,
                'lines'          => $line_list,
                'shop_type'      => $shop_type,
                'types'          => $types,
                'dimensions'     => $dimensions,
                'directions'     => $directions,
                'account_types'  => $account_types,
                'estimated'      => $estimated,
            )
        );
    }

    /**
     * 添加客户
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function create() {
        // 表单校验
        $this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|numeric');
        $this->form_validation->set_rules('name', '姓名', 'trim|required');
        $this->validate_form();
        // 数据处理
        $data = $this->_deal_user_data();
        // 验证客户手机号是否唯一
        if(!$this->MCustomer->check_mobile_unique($data['mobile'])){
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '手机号已经被注册过，请更换其他手机号',
                )
            );
        }

        //判断该用户是否是潜在客户
        $exists = $this->MPotential_customer->get_one('id', array('mobile' => $data['mobile']));
        if (count($exists) == 0) {
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '该用户不是潜在客户，请确认',
                )
            );
        }

        $password = $this->userauth->get_rand_pass();
        // 根据salt创建密码
        $this->_create_salt();
        $data['password']  = $this->create_password($password, $this->_salt);
        $data['salt'] = $this->_salt;
        $data['created_time'] = $this->input->server("REQUEST_TIME");
        $data['status'] = C('customer.status.new.code');
        $data['is_active'] = C('customer.status.valid.code');
        // 如果添加的是KA客户，需要审核
        if ($_POST['customerType'] == C('customer.type.KA.value')) {
            if ($_POST['account_type'] == C('customer.account_type.child.value')) {
                $parent = $this->MCustomer->get_one('*', ['mobile' => $_POST['parent_mobile'], 'customer_type' => C('customer.type.KA.value'), 'account_type' => C('customer.account_type.parent.value'), 'status >' => C('customer.status.invalid.code')]);
                if (empty($parent)) {
                    $this->_return_json(
                        array(
                            'status' => C('status.req.failed'),
                            'msg'    => '母账号不存在，请重新输入',
                        )
                    );
                }
            }
            $data['is_active'] = C('customer.status.invalid.code');
        }
        // 客户添加，入库
        $site_url = C('shortlink.chu');
        $sms_pattern = C('register_msg.sms_pattern_chu');
        if($insert_id = $this->MCustomer->create($data)) {
            // 添加图片
            if(!empty($this->input->post('pic_urls', TRUE))){
                $pic_urls = $this->input->post('pic_urls', TRUE);
                $this->MCustomer_image->create_imgs($pic_urls, $insert_id, C('customer_image.owner_type.customer'));
            }
            //客户添加成功后根据客户的手机类型不同发送不同的短信
            $content = sprintf($sms_pattern, $password, $site_url );
            $this->_return_json(
                array(
                    'status'  => C('status.req.success'),
                    'msg'     => '客户添加成功',
                    'content' => $content,
                    'info'    => array('id' => $insert_id)
                )
            );
        } else {
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '客户添加失败'
                )
            );
        }
    }

    /**
     * 修改客户页面
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();
        // 数据处理
        $data = $this->MCustomer->get_one('*', array('id' => $this->input->post('id', TRUE)));
        unset($data['salt']);
        unset($data['password']);

        //$data['account_type'] = $data['account_type'];
        //unset($data['account_type']);

        $img_data = $this->MCustomer_image->get_lists('*', array('owner_type' => C('customer_image.owner_type.customer'), 'owner_id' => $_POST['id'], 'status !=' => C('status.common.del')));
        $img_data = array_column($img_data, 'url');
        $data['is_uploaded'] = count($img_data);
        $data['pic_urls'] = $img_data;
        $where = array('status' => 1);
        $province_list = $this->MLocation->get_lists('*', array('upid' => '0'));
        $line_list = $this->MLine->get_lists('*', array('status' => C('status.common.success')));
        // 商家的类别
        $shop_type = array_values(C('customer_type.top'));
        if($data['invite_id'] != 0) {
            $invitor = $this->MCustomer->get_one('*', array('id' => $data['invite_id']));
            $data['invitor'] = $invitor;
        }
        // 若是母账号 需要获取该母账号的子账号
        $child_count = 0;
        $children = [];
        if($data['account_type'] == C('customer.account_type.parent.value')) {
            $children = $this->MCustomer->get_lists('id, name, shop_name, mobile', ['parent_mobile' => $data['mobile'], 'account_type' => C('customer.account_type.child.value')]);
            $child_count = count($children);
        }
        // 如果是子账号,则查询该子账号对应的母账号店铺名称
        if($data['account_type'] == C('customer.account_type.child.value') && !empty($data['parent_mobile'])) {
            $parent = $this->MCustomer->get_one('id, name, shop_name', ['mobile' => $data['parent_mobile'], 'status >' => C('status.common.del')]);
            $data['parent_shop_name'] = empty($parent) ? '' : $parent['shop_name'];
        }
        $data['child_count'] = $child_count;
        $dimensions = array_values(C('customer.dimension'));
        $directions = array_values(C('customer.direction'));
        $types = array_values(C('customer.type'));
        $account_types = array_values(C('customer.account_type'));
        $estimated = array_values(C('customer.estimated'));
        $banks = array_values(C('customer.bank'));
        $billing_cycles = array_values(C('customer.billing_cycle'));
        $directions = array_values(C('customer.direction'));


        $half_month = [];
        $first_month_start = intval(C('customer.ka_date.half_month.first.start'));
        $first_month_end = intval(C('customer.ka_date.half_month.first.end'));
        $tmp_half_month=[];
        for ($i=$first_month_start; $i<=$first_month_end; $i++) {
            $tmp_half_month[] = ['name' => $i.'号', 'value' => $i];
        }
        $half_month[] = $tmp_half_month;
        $next_month_start = intval(C('customer.ka_date.half_month.next.start'));
        $next_month_end = intval(C('customer.ka_date.half_month.next.end'));
        $tmp_next_half_month = [];
        for ($i=$next_month_start; $i<=$next_month_end; $i++) {
            $tmp_next_half_month[] = ['name' => $i.'号', 'value' => $i];
        }
        $half_month[] = $tmp_next_half_month;


        $month = [];
        $start = intval(C('customer.ka_date.month.start'));
        $end = intval(C('customer.ka_date.month.end'));
        for ($i=$start; $i<=$end; $i++) {
            $month[] = ['name' => $i.'号', 'value' => $i];
        }
        $ka_dates = [
            'day'   => array_values(C('customer.ka_date.day')),
            'week'  => array_values(C('customer.ka_date.week')),
            'half_month' => $half_month,
            'month' => $month,
        ];
        $pay_dates = [];
        $start = intval(C('customer.pay_date.month.start'));
        $end = intval(C('customer.pay_date.month.end'));
        for ($i=$start; $i<=$end; $i++) {
            $pay_dates[] = ['name' => $i.'天', 'value' => $i];
        }
        $check_dates= array_merge(array('none'=>array(array('name'=>'无','value'=> 'none'))), $ka_dates,  array('offline_billing'=>array(array('name'=>'线下挂账', 'value'=>'offline_billing'))));
        // 返回结果
        $this->_return_json(
            array(
                'status'           => C('status.req.success'),
                'info'             => $data,
                'provinces'        => $province_list,
                'lines'            => $line_list,
                'shop_type'        => $shop_type,
                'types'            => $types,
                'dimensions'       => $dimensions,
                'directions'       => $directions,
                'account_types'    => $account_types,
                'estimated'        => $estimated,
                'banks'            => $banks,
                'billing_cycles'   => $billing_cycles,
                'check_dates'      => $check_dates,
                'invoice_dates'    => $ka_dates,
                'pay_dates'        => $pay_dates,
                'children'         => $children,
            )
        );
    }

    /**
     * 修改客户
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->_deal_user_data();
        $id = $_POST['id'];
        // 如果传递了geo信息，则设置为已定位状态
        if(!empty($data['lng']) && !empty($data['lat'])){
            $data['is_located'] = 1;
        }
       $this->db->trans_start();
        //判断手机号是否有改变  有改变的话需要更新所有子账号parent_mobile
        $local_customer_info  =  $this->MCustomer->get_one(['id', 'mobile', 'account_type'], ['id'=>$id]);
        $local_mobile =  $local_customer_info['mobile'];
        $account_type = $local_customer_info['account_type'];
        if (intval($account_type) ==  C('customer.account_type.parent.value') && trim($local_mobile)  != trim($_POST['mobile'])) {
            $children  = $this->MCustomer->get_lists(['id'],  ['parent_mobile'=> trim($local_mobile)]);
            if (!empty($children)) {
                $children_ids = array_column($children, 'id');
                $this->MCustomer->update_info(['parent_mobile'=> trim($_POST['mobile'])], ['in'=>['id'=>$children_ids]]);
            }
        }
        // 客户修改，入库
        $this->MCustomer->update_info($data, array('id' => $id));
        // 添加图片
        if(!empty($_POST['pic_urls'])){
            // 删除原有图片
            $this->MCustomer_image->false_delete(['owner_id' => $_POST['id'], 'owner_type' => C('customer_image.owner_type.customer')]);
            $pic_urls = $_POST['pic_urls'];
            $this->MCustomer_image->create_imgs($pic_urls, $_POST['id'], C('customer_image.owner_type.customer'));
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            error_log('执行事务时出错');
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '执行出错,请重新尝试',
                )
            );
        }

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'msg'    => '客户修改成功',
            )
        );
    }

    /**
     * 修改客户线路页面
     * @author yugang@dachuwang.com
     * @since 2015-03-20
     */
    public function edit_line_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->MCustomer->get_one('*', array('id' => $this->input->post('id', TRUE)));
        $province_list = $this->MLocation->get_lists('*', array('upid' => '0'));
        if($data['invite_id'] != 0) {
            $invitor = $this->MCustomer->get_one('*', array('id' => $data['invite_id']));
            $data['invitor'] = $invitor;
        }
        $addr_where['in'] = array('id' => array($data['province_id'], $data['city_id'], $data['county_id']));
        $province_str = $this->MLocation->get_lists('name', $addr_where);
        $province_str = implode(' ', array_column($province_str, 'name'));
        $data['province_str'] = $province_str;

        $where = array('status' => C('status.common.success'));
        $lines = $this->MLine->get_lists('*', $where);

        // 返回结果
        $this->_return_json(
            array(
                'status'        => C('status.req.success'),
                'info'          => $data,
                'province_list' => $province_list,
                'lines'         => $lines,
            )
        );
    }

    /**
     * 修改客户线路
     * @author yugang@dachuwang.com
     * @since 2015-03-20
     */
    public function edit_line() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->form_validation->set_rules('line_id', '线路ID', 'required|numeric');
        $this->validate_form();

        $data['line_id'] = $this->input->post('line_id', TRUE);
        // 客户修改，入库
        $this->MCustomer->update_info($data, array('id' => $this->input->post('id')));

        // 临时逻辑，将该客户未配送的订单的线路id也修改为当前线路id
        $where['user_id'] = $this->input->post('id', TRUE);
        $where['in'] = array('status' => array('2', '3'));
        $this->MOrder->update_info($data, $where);

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'msg'    => '客户修改成功',
            )
        );
    }

    /**
     * 批量修改客户线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function batch_edit_line() {
        // 表单校验
        $this->form_validation->set_rules('cid', 'ID', 'required');
        $this->form_validation->set_rules('line_id', '线路ID', 'required|numeric');
        $this->validate_form();

        $data['line_id'] = $this->input->post('line_id', TRUE);
        $cid = $this->input->post('cid', TRUE);
        // 客户修改，入库
        $this->MCustomer->update_info($data, array('in' => array('id' => $cid)));

        // 临时逻辑，将该客户未配送的订单的线路id也修改为当前线路id
        foreach ($cid as $v) {
            $where['user_id'] = $v;
            $where['in'] = array('status' => array('2', '3'));
            $this->MOrder->update_info($data, $where);
        }

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'msg'    => '客户线路修改成功',
            )
        );
    }

    /**
     * 删除客户
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function delete() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $del_id = $this->input->post('id', TRUE);
        $where = array('id' => $del_id);
        // 假删除数据
        $result = $this->MCustomer->false_delete($where);

        // 返回结果
        $this->_return($result);
    }


    /**
     * 客户登录
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     * @description 客户登录
     */
    public function login() {
        // 表单校验
        $this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|numeric');
        $this->form_validation->set_rules('password', '密码', 'required');
        $this->validate_form();

        $login_result = $this->userauth->login($_POST['mobile'], $_POST['password']);

        if(!empty($login_result)) {
            $this->_return_json($login_result);
        }

        // 返回结果
        $this->_return_json(
            array(
                'status' => C("userauth.default.id"),
                'msg'    => C("userauth.default.msg")
            )
        );
    }

    /**
     * 修改密码
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     * @description 客户修改个人密码
     */
    public function change_password() {
        // 表单校验
        $this->form_validation->set_rules('password', '原密码', 'required');
        $this->form_validation->set_rules('newPassword', '新密码', 'required');
        $this->form_validation->set_rules('newRePassword', '确认密码', 'required|matches[newPassword]');
        $this->validate_form();
        $cur = $this->input->post('cur', TRUE);

        // 用于选择密码验证模式
        $app_category = $this->input->post('app_category');
        $app_category = $app_category == FALSE ? '' : $app_category;

        // 数据处理
        // 验证客户输入密码是否正确
        $password = $this->create_password($this->input->post('password'), $cur['salt'], $app_category);
        if($password != $cur['password']) {
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '密码输入错误，请重新输入',
                )
            );
        }
        // 修改客户密码
        $new_password = $this->create_password($this->input->post('newPassword'), $cur['salt'], $app_category);
        $result = $this->MCustomer->update_by('id', $cur['id'], array('password' => $new_password));

        // 返回结果
        $this->_return($result);
    }

    /**
     * 获取个人信息
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     * @description 获取个人信息
     */
    public function baseinfo() {
        // 获取当前登录客户
        // $cur = $this->userauth->current();
        $user_id = $this->input->post('user_id', TRUE);
        $cur = $this->MCustomer->get_one('*', array('id' => $user_id));
        // 若是子账号只需要看子账号的，若是木账号，需要看子账号
        if(empty($cur['parent_mobile']) && isset($cur['mobile'])) {
            // 母账号
            $childs = $this->MCustomer->get_lists('id', array('parent_mobile' => $cur['mobile']));
            if($childs) {
                $where['in'] = array(
                    'user_id'   => array_merge([$user_id], array_column($childs, 'id'))
                );
            } else {
                $where['user_id'] = $user_id;
            }
        } else {
            $where['user_id'] = $user_id;
        }

        //计算每种状态的订单数目
        $status_dict = array(
            C('order.customer_side_status.wait_confirm.code'),
            C('order.customer_side_status.wait_receive.code'),
            C('order.customer_side_status.success.code'),
            C('order.customer_side_status.closed.code'),
        );

        //母订单只有运营关注
        //因此只需要两个状态1：全部；2：待审核
        foreach($status_dict as $v) {
            $where['customer_side_status'] = $v;
            $total[$v] = $this->MOrder->count($where);
        }

        //查看指定用户的订单或全部
        // 返回客户编辑需要的相关资料
        $data = array(
            'status'    => C('status.req.success'),
            'role'      => $cur['role_id'],
            'info'      => $cur,
            'order'     => $total,
        );
        // 返回结果
        $this->_return_json($data);
    }

    /**
     * 客户修改个人信息
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     * @description 修改个人信息
     */
    public function edit_personal_info_input() {
        // 获取当前登录客户
        $cur = $this->userauth->current();

        // 返回客户编辑需要的相关资料
        $provinces = $this->MLocation->list_province();
        $citys = $this->MLocation->get_sons($cur['province_id']);
        $countys = $this->MLocation->get_sons($cur['city_id']);
        $data = array(
            'status'    => C('status.req.success'),
            'type'      => $cur['type'],
            'info'      => $cur,
            'provinces' => $provinces,
            'citys'     => $citys,
            'countys'   => $countys,
        );

        // 返回结果
        $this->_return_json($data);
    }

    /**
     * 客户修改个人信息
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     * @description 修改个人信息
     */
    public function edit_personal_info() {
        // 获取当前登录客户
        $cur = $this->userauth->current();
        // 数据校验
        $this->form_validation->set_rules('name', '姓名', 'trim|required');
        $this->form_validation->set_rules('type', '类型', 'required|greater_than[1]');
        $this->form_validation->set_rules('provinceId', '省份', 'required');
        $this->form_validation->set_rules('cityId', '城市', 'required');
        $this->form_validation->set_rules('address', '详细地址', 'required');
        $this->validate_form();

        // 数据处理
        $data = $this->_deal_user_data();
        // 客户修改，入库
        $this->MCustomer->update_info($data, array('id' => $cur['id']));

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.common.success'),
                'type'   => $cur['type'],
                'msg'    => '客户资料修改成功',
            )
        );
    }

    /**
     * @author yugang@dachuwang.com
     * @description 重置密码
     * @since 2015-03-04
     */
    public function reset_password() {
        // 表单校验
        $this->form_validation->set_rules('uid', '客户ID', 'required|numeric');
        $this->validate_form();

        // 数据权限校验
        // $auth_uid = $this->MCustomer->get_auth_uid($this->input->post('uid', TRUE));
        // $this->check_dataset_validation('user', $auth_uid);
        // 数据处理
        $password = $this->userauth->get_rand_pass();
        $result = $this->MCustomer->reset_password($this->input->post('uid', TRUE), $password);
        //用户添加成功后根据用户的手机类型不同发送不同的短信
        $data = $this->MCustomer->get_one('*', array('id' => $this->input->post('uid', TRUE)));
        $msg = C('register_msg.sms_resetpwd_chu');

        $content = sprintf($msg, $password);

        // 返回结果
        $this->_return_json(
            array(
                'status'  => C('status.req.success'),
                'msg'     => '密码重置成功',
                'mobile' => $data['mobile'],
                'content' => $content
            )
        );
    }

    /**
     * 退出
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function logout() {
        $this->userauth->logout();
        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'msg'    => '退出成功'
            )
        );
    }

    /**
     * 修改客户状态
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function toggle_status() {
        // 表单校验
        $this->form_validation->set_rules('uid', '客户ID', 'required|numeric');
        $this->form_validation->set_rules('status', '状态值', 'numeric');
        $this->validate_form();

        // 数据权限校验
        // $auth_uid = $this->MCustomer->get_auth_uid($this->input->post('uid', TRUE));
        // $this->check_dataset_validation('user', $auth_uid);

        // 数据处理
        $uid = $this->input->post('uid', TRUE);
        $status = $this->input->post('status', TRUE);
        $result = $this->MCustomer->toggle_status($uid, $status);
        // 返回结果
        $this->_return(TRUE);
    }


    /**
     * 禁用客户
     * @author yugang@dachuwang.com
     * @since 2015-07-30
     */
    public function disable() {
        // 表单校验
        $this->form_validation->set_rules('uid', '客户ID', 'required|numeric');
        $this->form_validation->set_rules('remark', '禁用理由', 'required');
        $this->validate_form();

        // 判断客户是否有子订单处于未完成状态
        $children_ids = $this->MCustomer->get_children_ids($_POST['uid']);
        $finish_status = [C('order.status.closed.code'), C('order.status.success.code'), C('order.status.sales_return.code')];
        $count = $this->MSuborder->count(
            [
                'not_in' => ['status' => $finish_status],
                'in' => ['user_id' => $children_ids]
            ]
        );
        if ($count > 0) {
            $this->_return_json(
                [
                    'status' => C('status.req.failed'),
                    'msg'    => '该客户有未完成的订单，不能禁用！'
                ]
            );
        }

        // 禁用母账号同时会禁用子账号
        $this->MCustomer->update_info(['status' => C('customer.status.disabled.code')], ['in' => ['id' => $children_ids]]);
        $this->_return(true);
    }

    /**
     * 获取禁用客户的理由
     * @author yugang@dachuwang.com
     * @since 2015-09-01
     */
    public function get_disable_reason() {
        // 表单校验
        $this->form_validation->set_rules('uid', '客户ID', 'required|numeric');
        $this->validate_form();

        $reasons = $this->MWorkflow_log->get_lists(
            'id, log_info, remark, from_unixtime(created_time) created_time',
            [
                'obj_id' => $_POST['uid'],
                'edit_type' => C('workflow_log.edit_type.customer')
            ],
            ['id' => 'DESC']
        );

        $this->_return_json(
            [
                'status'  => C('status.req.success'),
                'reasons' => $reasons
            ]
        );
    }

    /**
     * 启用客户账号
     * @author yugang@dachuwang.com
     * @since 2015-07-30
     */
    public function enable() {
        $customer = $this->MCustomer->get_one('*', ['id' => $_POST['uid']]);

        // 启用子账号时，需要判断其母账号的状态是否正常
        if ($customer['account_type'] == C('customer.account_type.child.value')) {
            $parent = $this->MCustomer->get_one('*', ['mobile' => $customer['parent_mobile'], 'status >' => C('status.common.del')]);
            if (empty($parent) || $parent['status'] <= C('status.common.del')) {
                $this->_return_json(
                    [
                        'status' => C('status.req.failed'),
                        'msg'    => '该客户所属母账号处于禁用状态，不能启用'
                    ]
                );
            }
        }

        // 启用账号
        $this->MCustomer->update_info(['status' => C('customer.status.valid.code')], ['id' => $_POST['uid']]);
        $this->_return(true);
    }

    /**
     * 获取母账号信息
     */
    public function get_parent_info() {
        $customer = $this->MCustomer->get_one('id, name, shop_name, mobile, recieve_name, recieve_mobile, account_type, parent_mobile', ['id' => $_POST['customer_id'], 'status >' => C('status.common.del')]);
        if ($customer['account_type'] == C('customer.account_type.parent.value')) {
            $this->_return_json([
                'status' => C('status.req.success'),
                'data'   => $customer
            ]);
        }

        $parent = $this->MCustomer->get_one('id, name, shop_name, mobile, recieve_name, recieve_mobile, account_type, parent_mobile', ['mobile' => $customer['parent_mobile'], 'status >' => C('status.common.del')]);
        $this->_return_json([
            'status' => C('status.req.success'),
            'data'   => empty($parent) ? [] : $parent
        ]);
    }


    /**
     * 创建密码
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    protected function create_password($str, $salt, $app_category = '') {
        if($app_category == 'app') {
            return md5($str. $salt);
        }
        return md5(md5($str) . $salt);
    }

    /**
     * 创建盐
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    protected function _create_salt() {
        if(empty($this->_salt)) {
            $this->_salt = substr(md5(uniqid()), 0, 6);
        }
    }


    /**
     * 统计客户订单数量
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    private function _count_user_order($user_list) {
        $res = array();
        $uids = array_column($user_list, 'uid');
        $user_orders = $this->MOrder->count_by_uids($uids);
        foreach ($user_list as $v) {
            $v['order'] = empty($user_orders[$v['uid']]) ? "0" : $user_orders[$v['uid']];
            $res[] = $v;
        }

        return $res;
    }

    /**
     * 处理表单中的数据
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     * @description 将表单中的数据做处理，存入一个数组返回
     * @return 处理后的数组
     */
    private function _deal_user_data(){
        $data['role_id'] = C('user.normaluser.purchase.type');
        $data['name']   = isset($_POST['name']) ? $_POST['name'] : '';
        $data['mobile']   = isset($_POST['mobile']) ? $_POST['mobile'] : '';
        $data['province_id'] = isset($_POST['provinceId']) ? $_POST['provinceId'] : 0;
        $data['line_id'] = isset($_POST['lineId']) ? $_POST['lineId'] : 0;
        $data['city_id'] = isset($_POST['cityId']) ? $_POST['cityId'] : 0;
        $data['county_id'] = isset($_POST['countryId']) ? $_POST['countryId'] : 0;
        $data['is_link'] = isset($_POST['isLink']) ? $_POST['isLink'] : '';
        $data['is_located'] = isset($_POST['is_located']) ? $_POST['is_located'] : 0;
        $data['address'] = isset($_POST['address']) ? $_POST['address'] : '';
        $data['shop_name'] = isset($_POST['shopName']) ? $_POST['shopName'] : '';
        $data['shop_type'] = isset($_POST['shopType']) ? $_POST['shopType'] : '0';
        $data['invite_id'] = isset($_POST['invite_id']) ? $_POST['invite_id'] : 0;
        $data['invite_bd'] = isset($_POST['invite_bd']) ? $_POST['invite_bd'] : 0;
        $data['lng'] = isset($_POST['lng']) ? $_POST['lng'] : '';
        $data['lat'] = isset($_POST['lat']) ? $_POST['lat'] : '';
        $data['geo_hash'] = isset($_POST['geo_hash']) ? $_POST['geo_hash'] : '';
        $data['is_located'] = isset($_POST['is_located']) ? $_POST['is_located'] : 0;
        $data['direction'] = isset($_POST['direction']) ? $_POST['direction'] : '';
        $data['dimensions'] = isset($_POST['dimensions']) ? $_POST['dimensions'] : '';
        $data['customer_type'] = isset($_POST['customerType']) ? $_POST['customerType'] : '';
        $data['account_type'] = isset($_POST['account_type']) ? $_POST['account_type'] : 0;
        $data['bank'] = isset($_POST['bank']) ? $_POST['bank'] : 0;
        $data['sub_bank'] = isset($_POST['sub_bank']) ? $_POST['sub_bank'] : '';
        $data['bank_account'] = isset($_POST['bank_account']) ? $_POST['bank_account'] : '';
        $data['parent_mobile'] = isset($_POST['parent_mobile']) ? $_POST['parent_mobile'] : '';
        $data['billing_cycle'] = isset($_POST['billing_cycle']) ? $_POST['billing_cycle'] : C('customer.billing_cycle.none.value');
        $data['check_date'] = isset($_POST['check_date']) ? $_POST['check_date'] : '';
        $data['invoice_date'] = isset($_POST['invoice_date']) ? $_POST['invoice_date'] : '';
        $data['updated_time'] = $this->input->server("REQUEST_TIME");
        $data['remark'] = isset($_POST['remark']) ? $_POST['remark'] : '';
        $data['greens_meat_estimated'] = isset($_POST['greens_meat_estimated']) ? $_POST['greens_meat_estimated'] : 0;
        $data['rice_grain_estimated'] = isset($_POST['rice_grain_estimated']) ? $_POST['rice_grain_estimated'] : 0;
        $data['is_active'] = isset($_POST['is_active']) ? $_POST['is_active'] : 0;
        $data['recieve_name'] = isset($_POST['recieve_name']) ? $_POST['recieve_name'] : '';
        $data['recieve_mobile'] = isset($_POST['recieve_mobile']) ? $_POST['recieve_mobile'] : '';
        $data['invoice_title'] = isset($_POST['invoice_title']) ? $_POST['invoice_title'] : '';
        $data = array_filter($data);
        $data['pay_date'] = isset($_POST['pay_date']) ? $_POST['pay_date'] : 0;
        return $data;
    }

    /**
     * 处理列表数据
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    private function _format_list($list) {
        $result = array();
        if(empty($list)) {
            return $result;
        }

        $line_list = $this->MLine->get_lists('id, name');
        $line_dict = array_combine(array_column($line_list, 'id'), array_column($line_list, 'name'));

        $order_count_list = $this->MOrder->count_by_cid(array_column($list, 'id'));
        $sale_user_ids = array_column($list, 'invite_id');
        $sale_am_ids = array_column($list, 'am_id');
        $sale_user_ids = array_merge($sale_user_ids, $sale_am_ids);
        $sale_user_ids = array_unique($sale_user_ids);
        $sale_user_ids = array_filter($sale_user_ids);
        if(!empty($sale_user_ids)) {
            $sale_user_list = $this->MUser->get_lists('id, name, mobile', array('in' => array('id' => $sale_user_ids)));
            $sale_ids = array_column($sale_user_list, 'id');
            $sale_dict = array_combine($sale_ids, $sale_user_list);
        }else{
            $sale_dict = [];
        }

        $cids = array_column($list, 'id');
        $image_list = $this->MCustomer_image->get_lists('*', ['owner_type' => C('customer_image.owner_type.customer'), 'in' => ['owner_id' => $cids]]);
        foreach ($image_list as $image) {
            $image_dict[$image['owner_id']][] = $image;
        }

        // 城市
        $city_list = $this->MLocation->get_lists('id, name', array('upid' => '0'));
        $city_dict = array_combine(array_column($city_list, 'id'), array_column($city_list, 'name'));
        // 商家的类别
        $shop_type = array_values(C('customer_type.top'));
        $shop_type_dict = array_combine(array_column($shop_type, 'id'), array_column($shop_type, 'name'));
        // 系统类别
        $sites = array_values(C('site.code'));
        $site_dict = array_combine(array_column($sites, 'id'), array_column($sites, 'name'));
        // 客户类型
        $customer_types = array_values(C('customer.type'));
        $customer_type_dict = array_combine(array_column($customer_types, 'value'), array_column($customer_types, 'name'));
        // 店铺规模
        $dimensions = array_values(C('customer.dimension'));
        $dimension_dict = array_combine(array_column($dimensions, 'value'), array_column($dimensions, 'name'));

        foreach ($list as $k => $v) {
            // 销售信息
            $bd_info = isset($sale_dict[$v['invite_id']]) ? $sale_dict[$v['invite_id']] : [];
            $bd_info['role'] = 'BD';
            $am_info = isset($sale_dict[$v['am_id']]) ? $sale_dict[$v['am_id']] : [];
            $am_info['role'] = 'AM';
            if($v['invite_id'] == C('customer.public_sea_code') || $v['status'] == C('customer.status.no_bd.code')) {
                $v['sale'] = ['role' => '公海客户', 'name' => '无对应销售', 'mobile' => ''];
            } elseif ($v['status'] == C('customer.status.allocated.code')) {
                $v['sale'] = $am_info;
            } else {
                $v['sale'] = $bd_info;
            }

            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            $sms_init = $this->MSms_log->get_one(
                'content', array(
                    'mobile' => $v['mobile']
                ),
                'created_time desc'
            );
            if($sms_init) {
                $v['init_sms'] = $sms_init['content'];
            }
            $v['site_name'] = isset($site_dict[$v['site_id']]) ? $site_dict[$v['site_id']] : '';
            $v['line_name'] = isset($line_dict[$v['line_id']]) ? $line_dict[$v['line_id']] : '';
            $v['shop_type_name'] = isset($shop_type_dict[$v['shop_type']]) ? $shop_type_dict[$v['shop_type']] : '';
            $v['city_name'] = isset($city_dict[$v['province_id']]) ? $city_dict[$v['province_id']] : '';
            $v['dimension_name'] = isset($dimension_dict[$v['dimensions']]) ? $dimension_dict[$v['dimensions']] : '';
            $v['customer_type_name'] = isset($customer_type_dict[$v['customer_type']]) ? $customer_type_dict[$v['customer_type']] : '';
            $v['image'] = isset($image_dict[$v['id']]) ? $image_dict[$v['id']] : [];
            $v['order_count'] = 0;
            foreach ($order_count_list as $order_count) {
                if($v['id'] == $order_count['user_id']) {
                    $v['order_count'] = $order_count['count'];
                }
            }
            if($v['customer_type']==C('customer.type.KA.value') && $v['is_active'] == C('customer.status.invalid.code')) {
                $v['ka_status'] = '待审核';
            }
            unset($v['password']);
            unset($v['salt']);
            $result[] = $v;
        }

        return $result;
    }

    /**
     * @author: liaoxianwen@dachuwang.com
     * @description
     */
    public function set_status() {
        $this->MCustomer->update_info(
            array(
                'status'   => $_POST['status']
            ),
            $_POST['where']
        );
        $this->_return_json(
            array(
                'status'    => C('tips.code.op_success'),
                'msg' => '设置成功'
            )
        );
    }


    /*
     * @author: wangyang@dachuwamg.com
     * @description: 新用户统计
     */
    public function customer_new_stats($tag=1,$stime=0,$etime=0,$price_category='total_price'){
        $result = array();
        $site_id = isset($tag) ? $tag : C('site.dachu');
        //默认取得今天的数据
        $left_date = strtotime(date('Y/m/d'));
        $right_date = strtotime(date('Y/m/d')) + 24 * 60 * 60 ; //默认1天
        $start_time = isset($stime) ? strtotime(date('Y/m/d',$stime)) : $left_date;
        $end_time = isset($etime) ? (strtotime(date('Y/m/d',$etime)) + 24*60*60) : $right_date;
        $time_span = ceil(($end_time - $start_time) / (60 * 60 * 24));

        // 统计time_span内的订单情况
        for ($i=0; $i < $time_span; $i++) {
            $time = $start_time + $i * 24 * 60 * 60;
            $where = array();
            $where['site_id'] = $site_id;
            $where['created_time <'] = $time + 24 * 60 * 60;
            $where['created_time >='] = $time;
            $customer_new = $this->MCustomer->count($where); //新增用户数

            $result[] = array(
                'date' => date('Y-m-d', $time),
                'customer_new' => $customer_new,
            );
        }

        $this->_return_json(
            array(
                'status'  => 0,
                'msg'     => 'success',
                'data'   => $result,
            )
        );

    }

    /*
     * @author: wangyang@dachuwamg.com
     * @description: 总用户统计
     */
    public function customer_total_stats($tag=1,$stime=0,$etime=0,$price_category='total_price'){
        $result = array();
        $site_id = isset($tag) ? $tag : C('site.dachu');
        //默认取得今天的数据
        $left_date = strtotime(date('Y/m/d'));
        $right_date = strtotime(date('Y/m/d')) + 24 * 60 * 60 ; //默认1天
        $start_time = isset($stime) ? strtotime(date('Y/m/d',$stime)) : $left_date;
        $end_time = isset($etime) ? (strtotime(date('Y/m/d',$etime)) + 24*60*60) : $right_date;
        $time_span = ceil(($end_time - $start_time) / (60 * 60 * 24));

        // 统计time_span内的订单情况
        for ($i=0; $i < $time_span; $i++) {
            $time = $start_time + $i * 24 * 60 * 60;
            $where = array();
            $where['site_id'] = $site_id;
            $where['created_time <'] = $time + 24 * 60 * 60;
            $customer_total = $this->MCustomer->count($where); //总用户数

            $result[] = array(
                'date' => date('Y-m-d', $time),
                'customer_total' => $customer_total,
            );
        }

        $this->_return_json(
            array(
                'status'  => 0,
                'msg'     => 'success',
                'data'   => $result,
            )
        );
    }
    /*
     * @author: wangyang@dachuwamg.com
     * @description: 登陆用户统计
     */
    public function customer_updated_stats($tag=1,$stime=0,$etime=0,$price_category='total_price'){
        $result = array();
        $site_id = isset($tag) ? $tag : C('site.dachu');
        //默认取得今天的数据
        $left_date = strtotime(date('Y/m/d'));
        $right_date = strtotime(date('Y/m/d')) + 24 * 60 * 60 ; //默认1天
        $start_time = isset($stime) ? strtotime(date('Y/m/d',$stime)) : $left_date;
        $end_time = isset($etime) ? (strtotime(date('Y/m/d',$etime)) + 24*60*60) : $right_date;
        $time_span = ceil(($end_time - $start_time) / (60 * 60 * 24));

        // 统计time_span内的订单情况
        for ($i=0; $i < $time_span; $i++) {
            $time = $start_time + $i * 24 * 60 * 60;
            $where = array();
            $where['site_id'] = $site_id;
            $where['updated_time <'] = $time + 24 * 60 * 60;
            $where['updated_time >='] = $time;
            $customer_updated = $this->MCustomer->count($where); //新增用户数
            $result[] = array(
                'date' => date('Y-m-d', $time),
                'customer_updated' => $customer_updated,
            );
        }

        $this->_return_json(
            array(
                'status'  => 0,
                'msg'     => 'success',
                'data'   => $result,
            )
        );
    }

    /**
     * 设置客户所属销售
     * @author yugang@dachuwang.com
     * @since 2015-04-27
     */
    public function set_sales() {
        // 表单校验
        $this->form_validation->set_rules('cids', '客户', 'required');
        $this->form_validation->set_rules('userId', '接收销售', 'required');
        $this->validate_form();

        if (is_array($_POST['cids'])) {
            $cids = $_POST['cids'];
        } else {
            $cids = explode(',', $_POST['cids']);
        }
        $customer_list = $this->MCustomer->get_lists('id', array('in' => array('id' => $cids)));
        foreach ($customer_list as $customer) {
            $data['invite_id'] = $_POST['userId'];
            // 更新客户的invite_id和状态
            $this->MCustomer->update_info($data, array('id' => $customer['id']));
        }

        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'msg'    => '设置成功',
            )
        );
    }

    /**
     * @interface:通过uids获取客户信息
     *
     * @author yuanxiaolin@dachuwang.com
     */
    public function lists_by_uids(){
    	try {
    		$uids = $this->input->post('uids');
    		$result = array();
    		if(empty($uids)){
    			throw new Exception('uids can not be empty');
    		}

    		$uids = explode('-', $uids);

    		$result=$this->MCustomer->lists_by_uids($uids);

    		$this->_return_json(array('status'=>0,'msg'=>$result));
    	} catch (Exception $e) {
    		$this->_return_json(array('status'=>-1,'msg'=>$e->getMessage()));
    	}
    }

    public function get_id_by_mobile() {
        $mobiles = empty($_POST['mobiles']) ? [] : $_POST['mobiles'];
        $response = array(
            'status' => C('tips.code.op_failed'),
            'msg' => '参数错误'
        );
        if($mobiles && is_array($mobiles)) {
            $customers = $this->MCustomer->get_lists__Cache120('*', array('in' => array('mobile' => $mobiles)));
            if($customers) {
                $response = array(
                    'status' => C('tips.code.op_success'),
                    'msg' => 'success',
                    'list' => $customers
                );
            }
        }
        $this->_return_json($customers);
    }

    /**
     * 待移交客户列表
     * @author yugang@dachuwang.com
     * @since 2015-06-11
     */
    public function lists_transfer() {
        // 表单校验
        $this->form_validation->set_rules('provinceId', '省份', 'required|numeric|greater_than[1]');
        $this->form_validation->set_rules('customerType', '客户类型', 'trim|required');
        $this->validate_form();

        // 参数解析&数据处理
        $page = $this->get_page();
        $where = array();
        $where['status >'] = C('status.common.del');

        // 所属城市
        if(!empty($_POST['provinceId'])) {
            $where['province_id'] = $_POST['provinceId'];
        }
        // 所属线路
        if(!empty($_POST['lineId'])) {
            $where['line_id'] = $_POST['lineId'];
        }
        // 根据关键词搜索
        if (! empty($_POST['key'])) {
            // 如果输入关键词为数字，则匹配手机号
            if (preg_match("/^\d{1,11}$/", $_POST['key'])) {
                $where['like'] = array (
                    'mobile' => $_POST['key']
                ) ;
            } else {
                $where['like'] = array (
                    'shop_name' => $_POST['key']
                ) ;
            }
        }
        // 所属销售
        if (!empty($_POST['saleId']) && $_POST['saleId'] > 0){
            $where['invite_id'] = $_POST['saleId'];
        }

        // 有无下单记录
        $order_users = $this->MOrder->get_lists('distinct(user_id) user_id', ['status !=' => C('order.status.closed.code')]);
        $order_user_ids = array_column($order_users, 'user_id');
        if (!empty($_POST['orderRecord'])) {
            if ($_POST['orderRecord'] == C('customer.order_record.with.code')) {
                $where['in']['id'] = $order_user_ids;
            } elseif ($_POST['orderRecord'] == C('customer.order_record.without.code')) {
                $where['not_in']['id'] = $order_user_ids;
            }
        }

        //暂时不处理前端关于排序的请求
        $order = array('created_time' => 'desc');
        $arr = $this->_get_lists($where, $order, $page);
        $list = $arr['list'];
        foreach ($list as &$item) {
            if (in_array($item['id'], $order_user_ids)) {
                $item['order_record'] = '有';
            } else {
                $item['order_record'] = '无';
            }
        }
        unset($item);
        $arr['list'] = $list;

        // 返回结果
        $this->_return_json($arr);
    }


    /**
     * 更新客户geo信息，tms调用
     * @author yugang@dachuwang.com
     * @since 2015-07-31
     *
     */
    public function update_geo() {
        // 表单校验
        $this->form_validation->set_rules('id', 'id', 'required|numeric|greater_than[1]');
        $this->form_validation->set_rules('lng', '经度', 'trim|required');
        $this->form_validation->set_rules('lat', '维度', 'trim|required');
        $this->validate_form();

        $this->MCustomer->update_info(['lng' => $_POST['lng'], 'lat' => $_POST['lat']], ['id' => $_POST['id']]);
        $this->_return(true);
    }

    /**
     * 获取bd的私海客户信息
     * @author maqiang@dachuwang.com
     * @since 2015-08-24
     *
     */
    public function get_private_sea_cutomer(){
        $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
        $this->validate_form();

       $page  = $this->get_page();

        $where = [];
        $where['invite_id']  =  $_POST['bd_id'];
        $where['status >']  = C('customer.status.invalid.code');
        if (isset($_POST['shop_name']) &&  trim($_POST['shop_name']) != '') {
            $where['like'] = ['shop_name'=>trim($_POST['shop_name'])];
        }
        $customer_lists = $this->MCustomer->get_lists(['id', 'shop_name', 'created_time'],$where);
        $potential_customer_lists =  $this->MPotential_customer->get(['id', 'shop_name',  'created_time'], $where);

        foreach ($customer_lists as  &$customer) {
            $customer['is_potential']  =  0;
        }

        foreach ($potential_customer_lists as &$potential_customer){
            $potential_customer['is_potential'] = 1;
        }

        $customer_lists  = array_merge($customer_lists, $potential_customer_lists);

        $created_time_lists =  array_column($customer_lists, 'created_time');
        array_multisort($created_time_lists,  SORT_DESC ,$customer_lists);

        $count  = count($customer_lists);
        $paged_customer_lists  = [];
        if  ($count>0){
            $paged_customer_lists  =  array_slice($customer_lists, $page['offset'], $page['page_size']);
        }
        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'list'    =>$paged_customer_lists,
                'total'  => $count
            )
        );
    }

    /**
     * 修改账户类型
     * @author maqiang@dachuwang.com
     * @param int $id 需要修改账户类型的identifier
     * @param string $new_parent_mobile 新的母账号手机号
     * @since 2015-07-07
     */
   private function _change_account_type($id, $new_parent_mobile){
       //获取该母账号的手机号
       $current_customer = $this->MCustomer->get_one('mobile',['id'=>$id]);
       $current_mobile = $current_customer['mobile'];

       //获取该母账号下的子账号
       $current_child_customers = $this->MCustomer->get_lists('id',['parent_mobile'=>$current_mobile]);
       if (count($current_child_customers)>0) {
           //检查新手机号是否存在
           $parent = $this->MCustomer->get_one('id', array('mobile' => $new_parent_mobile, 'account_type' => C('customer.account_type.parent.value'), 'status >' => C('customer.status.invalid.code')));
           if (empty($parent)) {
               $this->_return_json(
                   array(
                       'status' => C('status.req.failed'),
                       'msg'    => '母账号不存在，请重新输入',
                   )
               );
           }
           //更新所有子账号的母账号
           $condition =  array();
           $current_child_customers = array_column($current_child_customers, 'id');
           $condition['in'] = array('id'=> $current_child_customers);
           $conditon['status'] =  C('customer.status.valid.code');
           $this->MCustomer->update_info(array('parent_mobile'=> $new_parent_mobile), $condition);
       }
       //更新账户类型
   }

   /**
    * 修改客户类型
    * @author maqiang@dachuwang.com
    * @param int $id 需要更改客户的identifer
    * @param int $new_customer_type 客户类型
    * @since 2015-07-07
    */
   private function _change_customer_type($id, $new_customer_type){
       //获取该母账号信息
       $current_customer = $this->MCustomer->get_one('account_type, customer_type, mobile, parent_mobile',['id'=>$id]);
       $account_type = $current_customer['account_type'];
       $customer_type  = $current_customer['customer_type'];
       $mobile = $current_customer['mobile'];
       $parent_mobile = $current_customer['parent_mobile'];
       if($customer_type != $new_customer_type) {
           //当客户类型有改动的时候需要同步修改关联的子母账号
           $param = array();
           $param[] = $new_customer_type;
           $condition = 'where 1=1 and status >'.C('customer.status.invalid.code');
           if ($account_type == C('customer.account_type.parent.value')){
               //账号为母账号 需要修改子账号
               $condition .= ' and (parent_mobile = ? or mobile = ?)';
               $param[] = $mobile;
               $param[] = $mobile;
           }else{
               $condition .= " and (parent_mobile = ? or mobile = ?)";
               $param[] = $parent_mobile;
               $param[] = $parent_mobile;
           }
           $sql = "update t_customer set customer_type = ? $condition";
           $this->db->query($sql, $param);
       }
   }

   private function _get_month_firstday() {
       return strtotime(date('Y-m-01', time()));
   }

   private function getStartAndEnd() {
        $res = [];
        $time_type = isset($_POST['time_type']) ? $_POST['time_type'] : '';
        switch($time_type) {
        case 'by_day':
            $res['start'] = strtotime('today');
            $res['end']   = strtotime('now');
            break;
        case 'by_week':
            $res['start'] = strtotime('this Monday')>strtotime('now') ? strtotime('last Monday'):strtotime('this Monday');
            $res['end'] = strtotime('now');
            break;
        case 'by_month':
            $res['start'] = $this->_get_month_firstday();
            $res['end'] = strtotime('now');
            break;
        case 'all':
            $res['start'] = strtotime('2015-05-06');
            $res['end'] = strtotime('now');
            break;
        case 'optional':
            $res['start'] = isset($_POST['begin_time']) ? $_POST['begin_time'] : NULL;
            $res['end'] = isset($_POST['end_time']) ? $_POST['end_time'] + 86400 : NULL;
            if($res['start'] === NULL || $res['end'] === NULL) {
                return FALSE;
            }
            break;
        default:
            return FALSE;
            break;
        }
        //统计的最早时间从5月6号开始
        $latest = strtotime('2015-05-06');
        $res['start'] = $res['start']<$latest ? $latest:$res['start'];
        return $res;
   }
}
/* End of file customer.php */
/* Location: :./application/controllers/customer.php */
