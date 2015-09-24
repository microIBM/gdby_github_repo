<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 潜在潜在客户基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-24
 */
class Potential_customer extends MY_Controller {

    protected $_salt  = NULL;
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MLocation',
                'MDepartment',
                'MPotential_customer',
                'MCustomer',
                'MRole',
                'MPhone',
                'MOrder',
                'MSms_log',
                'MLine',
                'MCustomer_image',
                'MUser',
            )
        );
        $this->load->library(
            array(
                'form_validation',
                'filter_orders',
                'location'
            )
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 查看潜在客户
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function view() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->MPotential_customer->get_one('*', array('id' => $this->input->post('id', TRUE)));

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'info'   => $data,
            )
        );
    }


    /**
     * 潜在客户列表
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function lists() {
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
        if(isset($_POST['invite_id'])){
            // 潜在客户的invite_id为0的所有BD都可以共享看到 20150429 上海BD的需求
            if(isset($_POST['province_id']) && $_POST['province_id'] == C('open_cities.shanghai.id')) {
                $where['in'] = array('invite_id' => array($_POST['invite_id'], 0));
                $where['province_id'] = C('open_cities.shanghai.id');
            } else {
                $where['invite_id'] = $_POST['invite_id'];
            }
        }
        if(!empty($_POST['not_invite_id'])){
            $where['not_in']['invite_id'] = $_POST['not_invite_id'];
        }
        $where['status'] = C('status.common.success');
        $totalNumber = $this->MPotential_customer->count($where);
        if(isset($_POST['status']) && 'all' != $_POST['status']) {
            $where['status'] = $_POST['status'];
        }
        if(!empty($_POST['startTime'])) {
            $where['created_time >='] = $_POST['startTime'];
        }
        if(!empty($_POST['endTime'])) {
            $where['created_time <='] = $_POST['endTime'] + 86400;
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
        $order = array('created_time' => 'desc');
        //处理筛选
        if(!empty($_POST['conditions']) && is_array($_POST['conditions']) && !empty($_POST['conditions']['sift']) && is_array($_POST['conditions']['sift'])) {
            $temp = &$_POST['conditions']['sift'];
            if(!empty($temp['line'])) {
                $where['line_id'] = intval($temp['line']);
            }
            if(!empty($temp['site'])) {
                $where['site_id'] = intval($temp['site']);
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
            if(!empty($temp['customer_type'])) {
                $where['customer_type'] = $temp['customer_type'];
            }
        }
        /*
        foreach($where as $key => $v) {
            $where['t_customer.'.$key] = $v;
            unset($where[$key]);
        }
        $this->db->select('count(*)')->from('t_customer')->join('t_order', 't_customer.id = t_order.user_id', 'inner')->where($where)->get();
        var_dump($this->db->last_query());
        die;
        if(!empty($_POST['conditions']) && is_array($_POST['conditions']) && !empty($_POST['conditions']['sift']) && is_array($_POST['conditions']['sift']) && $_POST['conditions']['sift']['order_type']) {

        }*/
        // 查询数据
        $list = $this->MPotential_customer->get_lists('*', $where, $order, array(), $page['offset'], $page['page_size']);
        $total = $this->MPotential_customer->count($where);
        $list = $this->_format_list($list);
        $arr = array(
            'status' => C('status.req.success'),
            'list'   => $list,
            'total'  => $total,
            'total_number' => $totalNumber
        );

        // 返回结果
        $this->_return_json($arr);
    }

    //获取ids中已下单或者未下单的id，$order_status = 0 表示未下单 1表示已下单
    private function _get_customer_by_order_type(array $ids, $order_status=1) {
        if(!$ids) {
            return NULL;
        }
        $fields = ['user_id'];
        $where = [
            'in' => [
                'user_id' => $ids
            ],
            'status !=' => C('order.status.closed.code')
        ];
        $group_by = ['user_id'];
        $list = $this->MOrder->get_lists($fields, $where, $group_by);
        $answer = [];
        if($order_status === 1) {
            foreach($list as $v) {
                $answer[] = $v['user_id'];
            }
        } else if($order_status === 0) {
            foreach($list as $v) {
                if(!in_array($v['user_id'], $ids)) {
                    $answer[] = $v['user_id'];
                }
            }
        }
        return $answer;
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
        $where['status'] = C('status.common.success');
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
        // 暂时不处理前端关于排序的请求
        $order = array('created_time' => 'desc');
        // 查询数据
        $list = $this->MPotential_customer->get_lists('*', $where, $order, array(), $page['offset'], $page['page_size']);
        $total = $this->MPotential_customer->count($where);
        $list = $this->_format_list($list);
        foreach ($list as &$item) {
            $item['order_record'] = '无';
        }
        unset($item);
        $arr = array(
            'status' => C('status.req.success'),
            'list'   => $list,
            'total'  => $total,
        );

        // 返回结果
        $this->_return_json($arr);
    }

    /**
     * 添加潜在客户页面数据获取
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function create_input() {
        // 数据处理
        $where = array('status' => 1);
        $province_list = $this->MLocation->get_lists('*', array('upid' => '0'));

        // 返回结果
        $this->_return_json(
            array(
                'status'        => C('status.req.success'),
                'province_list' => $province_list,
            )
        );
    }

    /**
     * 添加潜在客户
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function create() {
        // 表单校验
        $this->form_validation->set_rules('provinceId', '省份', 'required|numeric|greater_than[1]');
        $this->form_validation->set_rules('shopName', '店铺名称', 'trim|required');
        $this->form_validation->set_rules('address', '详细地址', 'trim|required');
        $this->validate_form();
        // 数据处理
        $data = $this->_deal_user_data();
        // 验证潜在客户手机号是否唯一
        if(!empty($_POST['mobile']) && !$this->MPotential_customer->check_mobile_unique($data['mobile'])){
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '手机号已经被其他潜在客户注册过，请更换其他手机号',
                )
            );
        }
        // 验证是否与现有客户手机号重复
        if(!empty($_POST['mobile']) && !$this->MCustomer->check_mobile_unique($data['mobile'])){
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '手机号已经被其他客户注册过，请更换其他手机号',
                )
            );
        }

        // 当时子账号的时候 检查母账号是否正确
        if ($data['account_type'] == C('customer.account_type.child.value')) {
            $parent = $this->MCustomer->get_one('*', ['mobile' => $_POST['parent_mobile'], 'account_type' => C('customer.account_type.parent.value'), 'status >' => C('customer.status.invalid.code')]);
            if (empty($parent)) {
                $this->_return_json(
                     array(
                        'status' => C('status.req.failed'),
                        'msg'    => '母账号不存在，请重新输入',
                     )
                 );
            }else{
                //检查子账号的类型和母账号的类型是否一致
                if ($data['customer_type'] != $parent['customer_type']){
                    $this->_return_json(
                        array(
                            'status' => C('status.req.failed'),
                            'msg'    => '字母账号类型不一致，请确认',
                        )  
                    );          
                }
            }
        }

        $password = $this->userauth->get_rand_pass();
        // 根据salt创建密码
        $this->_create_salt();
        $data['password']  = $this->create_password($password, $this->_salt);
        $data['salt'] = $this->_salt;
        $data['created_time'] = $this->input->server("REQUEST_TIME");
        // 潜在客户添加，入库
        if($insert_id = $this->MPotential_customer->create($data)) {
            // 添加图片
            if(!empty($this->input->post('pic_urls', TRUE))){
                $pic_urls = $this->input->post('pic_urls', TRUE);
                $this->MCustomer_image->create_imgs($pic_urls, $insert_id, C('customer_image.owner_type.potential_customer'));
            }

            $this->_return_json(
                array(
                    'status'  => C('status.req.success'),
                    'msg'     => '潜在客户添加成功',
                    'site'    => $data['site_id'],
                    'info'    => array('id' => $insert_id)
                )
            );
        } else {
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '潜在客户添加失败'
                )
            );
        }
    }

    /**
     * 修改潜在客户页面
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function edit_input() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->MPotential_customer->get_one('*', array('id' => $this->input->post('id', TRUE), 'status !=' => C('status.common.del')));
        unset($data['salt']);
        unset($data['password']);

        $img_data = $this->MCustomer_image->get_lists('*', array('owner_type' => C('customer_image.owner_type.potential_customer'), 'owner_id' => $_POST['id'], 'status !=' => C('status.common.del')));
        $img_data = array_column($img_data, 'url');
        $data['is_uploaded'] = count($img_data);
        $data['pic_urls'] = $img_data;
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
                'info'           => $data,
                'provinces'      => $province_list,
                'lines'          => $line_list,
                'shop_type'      => $shop_type,
                'estimated'      => $estimated,
                'types'          => $types,
                'dimensions'     => $dimensions,
                'directions'     => $directions,
                'account_types'  => $account_types,
            )
        );
    }

    /**
     * 修改潜在客户
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function edit() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $data = $this->_deal_user_data();

        // 当时子账号的时候 检查母账号是否正确
        if ($data['account_type'] == C('customer.account_type.child.value')) {
            $parent = $this->MCustomer->get_one('*', ['mobile' => $_POST['parent_mobile'], 'account_type' => C('customer.account_type.parent.value'), 'status >' => C('customer.status.invalid.code')]);
            if (empty($parent)) {
                $this->_return_json(
                    array(
                        'status' => C('status.req.failed'),
                        'msg'    => '母账号不存在，请重新输入',
                    )
                );
            }else{
                //检查子账号的类型和母账号的类型是否一致
                if ($data['customer_type'] != $parent['customer_type']){
                    $this->_return_json(array('status' => C('status.req.failed'),
                        'msg'    => '子母账号类型不一致，请确认',
                        )
                    );
                }
            }
        }

        // 潜在客户修改，入库
        $this->MPotential_customer->update_info($data, array('id' => $this->input->post('id')));
        // 添加图片
        if(!empty($this->input->post('pic_urls', TRUE))){
            // 删除原有图片
            $this->MCustomer_image->false_delete(['owner_id' => $_POST['id'], 'owner_type' => C('customer_image.owner_type.potential_customer')]);
            $pic_urls = $this->input->post('pic_urls', TRUE);
            $this->MCustomer_image->create_imgs($pic_urls, $this->input->post('id', TRUE), C('customer_image.owner_type.potential_customer'));
        }

        // 返回结果
        $this->_return_json(
            array(
                'status' => C('status.req.success'),
                'msg'    => '潜在客户修改成功',
            )
        );
    }

    /**
     * 删除潜在客户
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function delete() {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric');
        $this->validate_form();

        // 数据处理
        $del_id = $this->input->post('id', TRUE);
        $where = array('id' => $del_id);
        // 假删除数据
        $result = $this->MPotential_customer->false_delete($where);

        // 返回结果
        $this->_return($result);
    }

    /**
     * 设置潜在客户所属销售
     * @author yugang@dachuwang.com
     * @since 2015-06-12
     */
    public function set_sales() {
        // 表单校验
        $this->form_validation->set_rules('pcids', '潜在客户', 'required');
        $this->form_validation->set_rules('userId', '接收销售', 'required');
        $this->validate_form();

        if (is_array($_POST['pcids'])) {
            $cids = $_POST['pcids'];
        } else {
            $cids = explode(',', $_POST['pcids']);
        }
        $customer_list = $this->MPotential_customer->get_lists('*', array('in' => array('id' => $cids)));
        foreach ($customer_list as $customer) {
            $data['invite_id'] = $_POST['userId'];
            // 更新客户的invite_id和状态
            $this->MPotential_customer->update_info($data, array('id' => $customer['id']));
        }

        $this->_return_json(
            array(
                'status' => C('tips.code.op_success'),
                'msg'    => '设置成功',
            )
        );

    }


    /**
     * 创建密码
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    protected function create_password($str, $salt) {
        return md5(md5($str) . $salt);
    }

    /**
     * 创建盐
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    protected function _create_salt() {
        if(empty($this->_salt)) {
            $this->_salt = substr(md5(uniqid()), 0, 6);
        }
    }

    /**
     * 处理表单中的数据
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     * @description 将表单中的数据做处理，存入一个数组返回
     * @return 处理后的数组
     */
    private function _deal_user_data(){
        $data['role_id'] = C('user.normaluser.purchase.type');
        $data['name']   = isset($_POST['name']) ? $_POST['name'] : '';
        $data['mobile']   = isset($_POST['mobile']) ? $_POST['mobile'] : '';
        $data['province_id'] = isset($_POST['provinceId']) ? $_POST['provinceId'] : 0;
        $data['line_id'] = isset($_POST['lineId']) ? $_POST['lineId'] : 0;
        $data['site_id'] = isset($_POST['siteId']) ? $_POST['siteId'] : C('site.dachu');
        $data['city_id'] = isset($_POST['cityId']) ? $_POST['cityId'] : 0;
        $data['county_id'] = isset($_POST['countryId']) ? $_POST['countryId'] : 0;
        $data['is_link'] = isset($_POST['isLink']) ? $_POST['isLink'] : '';
        $data['address'] = isset($_POST['address']) ? $_POST['address'] : '';
        $data['shop_name'] = isset($_POST['shopName']) ? $_POST['shopName'] : '';
        $data['shop_type'] = isset($_POST['shopType']) ? $_POST['shopType'] : '0';
        $data['invite_id'] = isset($_POST['invite_id']) ? $_POST['invite_id'] : 0;
        $data['invite_bd'] = isset($_POST['invite_bd']) ? $_POST['invite_bd'] : 0;
        $data['remark'] = isset($_POST['remark']) ? $_POST['remark'] : '';
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
        $data['billing_cycle'] = isset($_POST['billing_cycle']) ? $_POST['billing_cycle'] : '';
        $data['check_date'] = isset($_POST['check_date']) ? $_POST['check_date'] : '';
        $data['invoice_date'] = isset($_POST['invoice_date']) ? $_POST['invoice_date'] : '';
        $data['pay_date'] = isset($_POST['pay_date']) ? $_POST['pay_date'] : '';
        $data['updated_time'] = $this->input->server("REQUEST_TIME");

        $data['greens_meat_estimated'] = isset($_POST['greens_meat_estimated']) ? $_POST['greens_meat_estimated'] : 0;
        $data['rice_grain_estimated'] = isset($_POST['rice_grain_estimated']) ? $_POST['rice_grain_estimated'] : 0;
        $data['recieve_name'] = isset($_POST['recieve_name']) ? $_POST['recieve_name'] : '';
        $data['recieve_mobile'] = isset($_POST['recieve_mobile']) ? $_POST['recieve_mobile'] : '';
        $data = array_filter($data);
        return $data;
    }

    /**
     * 处理列表数据
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    private function _format_list($list) {
        $result = array();
        if(empty($list)) {
            return $result;
        }

        $line_list = $this->MLine->get_lists('id, name');
        $line_dict = array_combine(array_column($line_list, 'id'), array_column($line_list, 'name'));

        $sale_user_ids = array_column($list, 'invite_id');
        $sale_user_ids = array_unique($sale_user_ids);
        $sale_user_ids = array_filter($sale_user_ids);
        if(!empty($sale_user_ids)) {
            $sale_user_list = $this->MUser->get_lists('id, name, mobile', array('in' => array('id' => $sale_user_ids)));
            $sale_dict = array_combine(array_column($sale_user_list, 'id'), $sale_user_list);
        }else{
            $sale_dict = [];
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
            if($v['invite_id'] == C('customer.public_sea_code') || $v['status'] == C('customer.status.no_bd.code')) {
                $v['sale'] = ['role' => '公海客户', 'name' => '无对应销售'];
            } else {
                $v['sale'] = $bd_info;
            }
            $v['site_name'] = isset($site_dict[$v['site_id']]) ? $site_dict[$v['site_id']] : '';
            $v['line_name'] = isset($line_dict[$v['line_id']]) ? $line_dict[$v['line_id']] : '';
            $v['shop_type_name'] = isset($shop_type_dict[$v['shop_type']]) ? $shop_type_dict[$v['shop_type']] : '';
            $v['city_name'] = isset($city_dict[$v['province_id']]) ? $city_dict[$v['province_id']] : '';
            $customer_types = array_values(C('customer.type'));
            $customer_type_dict = array_combine(array_column($customer_types, 'value'), array_column($customer_types, 'name'));
            $dimensions = array_values(C('customer.dimension'));
            $dimension_dict = array_combine(array_column($dimensions, 'value'), array_column($dimensions, 'name'));
            $v['dimension_name'] = isset($dimension_dict[$v['dimensions']]) ? $dimension_dict[$v['dimensions']] : '';
            $v['customer_type_name'] = isset($customer_type_dict[$v['customer_type']]) ? $customer_type_dict[$v['customer_type']] : '';
            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']);
            unset($v['password']);
            unset($v['salt']);
            $result[] = $v;
        }

        return $result;
    }
}
/* End of file potential_customer.php */
/* Location: :./application/controllers/potential_customer.php */
