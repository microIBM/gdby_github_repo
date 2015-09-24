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
                'MProduct',
                'MCategory',
                'MSms_log',
                'MLine',
                'MCustomer_image',
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
        // 根据客户类型筛选
        if (!empty($_POST['customer_type']) && 'all' != $_POST['customer_type']) {
            // 未审核KA客户
            if ($_POST['customer_type'] == C('customer.list_type.KA_AUDIT.value')) {
                $where['customer_type'] = C('customer.list_type.KA.value');
                $where['is_active'] = C('customer.status.invalid.code');
            } else {
                $where['customer_type'] = $_POST['customer_type'];
            }
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
    private function _get_lists($where, $order, $page) {
        $total = $this->MCustomer->count($where);
        if($page['offset'] >= $total){
            $page['offset'] = 0;
        }
        $list = $this->MCustomer->get_lists('*', $where, $order, array(), $page['offset'], $page['page_size']);
        $sql = $this->db->last_query();
        $list = $this->_format_list($list);
        $arr = array(
            'status' => C('status.req.success'),
            'list'   => $list,
            'total'  => $total,
            'sql'    => $sql,
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
        $is_bdm = $cur['role_id'] == C('user.saleuser.BDM.type') ? TRUE : FALSE;
        $mygroup = array();
        $bd_arr = array(C('user.saleuser.BD.type'), C('user.saleuser.BDM.type'));
        $am_arr = array(C('user.saleuser.AM.type'), C('user.saleuser.SAM.type'));

        if($is_bdm) {
            $where_common['in'] = array('status' => array(C('customer.status.new.code'), C('customer.status.undone.code')));
            $db_group = $this->MUser->get_lists('id, name', array('in' => array('role_id' => $bd_arr, 'dept_id' => $dept_list), 'id !=' => $cur['id'], 'status !=' => C('status.user.del')));
        }else{
            $where_common['in'] = array('status' => array(C('customer.status.allocated.code')));
            $db_group = $this->MUser->get_lists('id, name', array('in' => array('role_id' => $am_arr, 'dept_id' => $dept_list), 'id !=' => $cur['id'], 'status !=' => C('status.user.del')));
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
        $shop_type = array_merge(C('customer_type.top'), C('customer_type.child'));
        // 客户类别
        $types = array_values(C('customer.type'));
        $dimensions = array_values(C('customer.dimension'));
        $directions = array_values(C('customer.direction'));
        $ka_types = array_values(C('customer.ka_type'));
        $banks = array_values(C('customer.bank'));
        $billing_cycles = array_values(C('customer.billing_cycle'));
        $directions = array_values(C('customer.direction'));
        $month = [];
        $start = intval(C('customer.ka_date.month.start'));
        $end = intval(C('customer.ka_date.month.end'));
        for ($i=$start; $i<=$end; $i++) {
            $month[] = ['name' => $i.'号', 'value' => $i];
        }
        $ka_dates = [
            'day'   => array_values(C('customer.ka_date.day')),
            'week'  => array_values(C('customer.ka_date.week')),
            'month' => $month,
        ];

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
                'ka_types'       => $ka_types,
                'banks'          => $banks,
                'billing_cycles' => $billing_cycles,
                'check_dates'    => $ka_dates,
                'invoice_dates'  => $ka_dates,
                'pay_dates'      => $ka_dates,
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
            if ($_POST['ka_type'] == C('customer.ka_type.child.value')) {
                $parent = $this->MCustomer->get_one('*', ['mobile' => $_POST['parent_mobile'], 'customer_type' => C('customer.type.KA.value'), 'ka_type' => C('customer.ka_type.parent.value'), 'status >' => C('customer.status.invalid.code')]);
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
        $img_data = $this->MCustomer_image->get_lists('*', array('owner_type' => C('customer_image.owner_type.customer'), 'owner_id' => $_POST['id'], 'status !=' => C('status.common.del')));
        $img_data = array_column($img_data, 'url');
        $data['is_uploaded'] = count($img_data);
        $data['pic_urls'] = $img_data;
        $where = array('status' => 1);
        $province_list = $this->MLocation->get_lists('*', array('upid' => '0'));
        $line_list = $this->MLine->get_lists('*', array('status' => C('status.common.success')));
        // 商家的类别
        $shop_type = array_merge(C('customer_type.top'), C('customer_type.child'));
        if($data['invite_id'] != 0) {
            $invitor = $this->MCustomer->get_one('*', array('id' => $data['invite_id']));
            $data['invitor'] = $invitor;
        }

        $dimensions = array_values(C('customer.dimension'));
        $directions = array_values(C('customer.direction'));
        $types = array_values(C('customer.type'));
        $ka_types = array_values(C('customer.ka_type'));
        $banks = array_values(C('customer.bank'));
        $billing_cycles = array_values(C('customer.billing_cycle'));
        $directions = array_values(C('customer.direction'));
        $month = [];
        $start = intval(C('customer.ka_date.month.start'));
        $end = intval(C('customer.ka_date.month.end'));
        for ($i=$start; $i<=$end; $i++) {
            $month[] = ['name' => $i.'号', 'value' => $i];
        }
        $ka_dates = [
            'day'   => array_values(C('customer.ka_date.day')),
            'week'  => array_values(C('customer.ka_date.week')),
            'month' => $month,
        ];

        // 返回结果
        $this->_return_json(
            array(
                'status'         => C('status.req.success'),
                'info'           => $data,
                'provinces'      => $province_list,
                'lines'          => $line_list,
                'shop_type'      => $shop_type,
                'types'          => $types,
                'dimensions'     => $dimensions,
                'directions'     => $directions,
                'ka_types'       => $ka_types,
                'banks'          => $banks,
                'billing_cycles' => $billing_cycles,
                'check_dates'    => $ka_dates,
                'invoice_dates'  => $ka_dates,
                'pay_dates'      => $ka_dates,
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
        // 如果传递了geo信息，则设置为已定位状态
        if(!empty($data['lng']) && !empty($data['lat'])){
            $data['is_located'] = 1;
        }
        // 客户修改，入库
        $this->MCustomer->update_info($data, array('id' => $_POST['id']));
        // 添加图片
        if(!empty($_POST['pic_urls'])){
            // 删除原有图片
            $this->MCustomer_image->false_delete(['owner_id' => $_POST['id'], 'owner_type' => C('customer_image.owner_type.customer')]);
            $pic_urls = $_POST['pic_urls'];
            $this->MCustomer_image->create_imgs($pic_urls, $_POST['id'], C('customer_image.owner_type.customer'));
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

        //计算每种状态的订单数目
        //从配置文件里取道所有的code
        $status_dict = array_column(
            array_values(
                C('order.status')
            ),
            'code'
        );

        //查看指定用户的订单或全部
        $where['user_id'] = $user_id;
        foreach($status_dict as $v) {
            if($v != -1) {
                $where['status'] = $v;
            }
            $total[$v] = $this->MOrder->count($where);
        }
        // 返回客户编辑需要的相关资料
        $data = array(
            'status'    => C('status.req.success'),
            'role'      => $cur['role_id'],
            'info'      => $cur,
            'order'     => $total,
        );
        $data['order']['100'] = 0;
        foreach ($this->_wait_status_arr as $item) {
            if(!empty($data['order'][$item])) {
                $data['order']['100'] += $data['order'][$item];
            }
        }
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
        $data['ka_type'] = isset($_POST['ka_type']) ? $_POST['ka_type'] : 0;
        $data['bank'] = isset($_POST['bank']) ? $_POST['bank'] : 0;
        $data['sub_bank'] = isset($_POST['sub_bank']) ? $_POST['sub_bank'] : '';
        $data['bank_account'] = isset($_POST['bank_account']) ? $_POST['bank_account'] : '';
        $data['parent_mobile'] = isset($_POST['parent_mobile']) ? $_POST['parent_mobile'] : '';
        $data['billing_cycle'] = isset($_POST['billing_cycle']) ? $_POST['billing_cycle'] : '';
        $data['check_date'] = isset($_POST['check_date']) ? $_POST['check_date'] : '';
        $data['invoice_date'] = isset($_POST['invoice_date']) ? $_POST['invoice_date'] : '';
        $data['pay_date'] = isset($_POST['pay_date']) ? $_POST['pay_date'] : '';
        $data['updated_time'] = $this->input->server("REQUEST_TIME");
        $data = array_filter($data);
        $data['remark'] = isset($_POST['remark']) ? $_POST['remark'] : '';
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
        $top_shop_type = array_values(C('customer_type.top'));
        $child_shop_type = array_values(C('customer_type.child'));
        $shop_type = array_merge($top_shop_type, $child_shop_type);
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
            if($v['ka_type'] && $v['is_active'] == C('customer.status.invalid.code')) {
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
        $this->form_validation->set_rules('receiverType', '接收者类型', 'required');
        $this->form_validation->set_rules('userId', '接收销售', 'required');
        $this->validate_form();

        if (is_array($_POST['cids'])) {
            $cids = $_POST['cids'];
        } else {
            $cids = explode(',', $_POST['cids']);
        }
        $customer_list = $this->MCustomer->get_lists('*', array('in' => array('id' => $cids)));
        foreach ($customer_list as $customer) {
            if ($_POST['receiverType'] == C('customer.customer_sea.private.old.code')){
                // 移交给AM需要更新状态
                $data['status'] = C('customer.status.allocated.code');
                $data['am_id'] = $_POST['userId'];
            } else {
                $data['invite_id'] = $_POST['userId'];
            }
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
        $this->form_validation->set_rules('siteId', '所属系统', 'trim|required');
        $this->form_validation->set_rules('saleType', '销售角色', 'trim|required');
        $this->form_validation->set_rules('customerType', '客户类型', 'trim|required');
        $this->validate_form();

        // 参数解析&数据处理
        $page = $this->get_page();
        $where = array();

        // 所属系统
        if(!empty($_POST['siteId'])) {
            $where['site_id'] = $_POST['siteId'];
        }
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
        // 是否超过首单7天
        if (isset($_POST['firstOrder'])){
            $is_over = !empty($_POST['firstOrder']);
            $user_ids = $this->MOrder->get_user_by_first_order($is_over);
            $where['in']['id'] = $user_ids;
        }
        // 客户类型
        if ($_POST['customerType'] == C('customer.customer_type.public_register_customer.code')) {
            // BD 公海新注册客户
            $where['status'] = C('customer.status.new.code');
            $where['invite_id'] = C('customer.public_sea_code');
        } elseif ($_POST['customerType'] == C('customer.customer_type.public_nobd_customer.code')) {
            // BD 公海完成订单但找不到注册BD的客户
            $where['status'] = C('customer.status.no_bd.code');
            $where['invite_id'] = C('customer.public_sea_code');
        } elseif ($_POST['customerType'] == C('customer.customer_type.bd_register_customer.code')
            || $_POST['customerType'] == C('customer.customer_type.bd_register_customer2.code')) {
            // BD 新注册客户
            $where['status'] = C('customer.status.new.code');
            $where['invite_id >'] = C('status.common.del');
        } elseif ($_POST['customerType'] == C('customer.customer_type.bd_undone_customer.code')) {
            // BD 新下单客户
            $where['in']['status'] = [C('customer.status.undone.code'), C('customer.status.unallocated.code')];
            $where['invite_id >'] = C('status.common.del');
        } elseif ($_POST['customerType'] == C('customer.customer_type.bd_unallocated_customer.code')) {
            // BD 完成首单客户
            $where['status'] = C('customer.status.unallocated.code');
            $where['invite_id >'] = C('status.common.del');
            $order_users = $this->MOrder->get_lists('distinct(user_id) user_id', ['status !=' => C('order.status.closed.code')]);
            if (!empty($order_users)){
                $order_user_ids = array_column($order_users, 'user_id');
                if (!empty($where['in']) && !empty($where['in']['id'])){
                    $order_user_ids = array_intersect($order_user_ids, $where['in']['id']);
                    $order_user_ids = array_unique($order_user_ids);
                }
                $where['in']['id'] = $order_user_ids;
            }
        } elseif ($_POST['customerType'] == C('customer.customer_type.am_customer.code')) {
            // AM 老客户
            $where['status'] = C('customer.status.allocated.code');
            $where['am_id >'] = C('status.common.del');
        }
        // 所属销售
        if (!empty($_POST['saleId']) && $_POST['saleId'] > 0 &&  $_POST['saleType'] == 'BD'){
            $where['invite_id'] = $_POST['saleId'];
        } elseif (!empty($_POST['saleId']) && $_POST['saleId'] > 0 &&  $_POST['saleType'] == 'AM') {
            $where['am_id'] = $_POST['saleId'];
        } elseif ($_POST['saleType'] == 'public') {
            $where['invite_id'] = C('customer.public_sea_code');
        }

        //暂时不处理前端关于排序的请求
        $order = array('created_time' => 'desc');
        $arr = $this->_get_lists($where, $order, $page);
        $user_arr = [];
        // 设置下首单后天数
        if (!empty($arr) && !empty($arr['list'])){
            $first_orders = $this->MOrder->count_first_order(array_column($arr['list'], 'id'));
            $first_order_map = array_combine(array_column($first_orders, 'user_id'), $first_orders);
            foreach ($arr['list'] as $v) {
                $v['first_order_day'] = isset($first_order_map[$v['id']]) ? $first_order_map[$v['id']]['first_order_day'] : 0;
                $user_arr[] = $v;
            }
            $arr['list'] = $user_arr;
        }

        // 返回结果
        $this->_return_json($arr);
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

}
/* End of file customer.php */
/* Location: :./application/controllers/customer.php */
