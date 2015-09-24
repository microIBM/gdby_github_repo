<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed') ;
/**
 * 用户基础服务
 *
 * @author yugang@dachuwang.com
 * @version : 1.0.0
 * @since : 2015-03-03
 */
class User extends MY_Controller {

    protected $_salt = NULL ;
    public $rand_pass = '' ;
    public function __construct () {
        parent::__construct() ;
        $this->load->model(array (
                'MLocation',
                'MDepartment',
                'MUser',
                'MRole',
                'MPhone',
                'MOrder',
                'MProduct',
                'MCategory'
        )) ;
        $this->load->library(array (
                'form_validation',
                'filter_orders'
        )) ;
        $this->rand_pass = str_split('ABCDEFGHJKLMNPQRSTWWWXYabcdefghjkmnpqrst23456789') ;
        shuffle($this->rand_pass) ;
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 查看用户
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function view () {
        $this->_return_json(array (
                'status' => C('status.req.success'),
                'info' => 'test ok'
        )) ;
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric') ;
        $this->validate_form() ;

        // 数据查询
        $data = $this->MUser->get_one('*', array (
                'id' => $this->input->post('id', TRUE)
        )) ;

        // 返回结果
        $this->_return_json(array (
                'status' => C('status.req.success'),
                'info' => $data
        )) ;
    }

    /**
     * 用户列表
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function lists () {
        // 参数解析&数据处理
        $page = $this->get_page() ;
        $where = array () ;
        // $where['status'] = C('status.common.success');
        if (isset($_POST['status']) && 'all' != $_POST['status']) {
            $where['status'] = $_POST['status'] ;
        } else {
            $where['status !='] = C('status.common.del');
        }
        if (! empty($_POST['roleId'])) {
            $where['role_id'] = $_POST['roleId'] ;
        }
        // 根据角色类型过滤：sale 、 BD 、AM
        if(! empty($_POST['roleType'])) {
            $bd_array = array(C('user.saleuser.BD.type'), C('user.saleuser.BDM.type'));
            $am_array = array(C('user.saleuser.AM.type'), C('user.saleuser.SAM.type'), C('user.saleuser.CM.type'));
            $sale_array = array_merge($bd_array, $am_array);

            if('sale' == $_POST['roleType']) {
                $where['in'] = array('role_id' => $sale_array);
            } elseif ('BD' == $_POST['roleType']) {
                $where['in'] = array('role_id' => $bd_array);
            } elseif ('AM' == $_POST['roleType']) {
                $where['in'] = array('role_id' => $am_array);
            }
        }
        if (! empty($_POST['startTime'])) {
            $where['created_time >='] = $_POST['startTime'] / 1000 ;
        }
        if (! empty($_POST['endTime'])) {
            $where['created_time <='] = $_POST['endTime'] / 1000 + 86400 ;
        }
        if (! empty($_POST['searchValue'])) {
            // 如果输入关键词为数字，则匹配手机号
            if (preg_match("/^\d{1,11}$/", $_POST['searchValue'])) {
                $where['like'] = array (
                        'mobile' => $_POST['searchValue']
                ) ;
            } else {
                $where['like'] = array (
                        'name' => $_POST['searchValue']
                ) ;
            }
        }
        //$where['role_id !='] = C('user.superadmin.admin.type') ;
        $order = array (
                'created_time' => 'desc'
        ) ;
        $list = $this->MUser->get_lists('*', $where, $order, array (), $page['offset'], $page['page_size']) ;
        $sql = $this->db->last_query() ;
        $total = $this->MUser->count($where) ;
        $list = $this->_format_list($list) ;
        $arr = array (
                'status' => C('status.req.success'),
                'sql' => $sql,
                'list' => $list,
                'total' => $total
        ) ;

        // 返回结果
        $this->_return_json($arr) ;
    }

    /**
     * 获取角色列表
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-07
     */
    public function role_list () {
        // 角色列表
        $role_list = $this->MRole->get_lists('id, name', array (
                'status' => 1,
                //'id !=' => C('user.superadmin.admin.type')
        )) ;
        $arr = array (
                'status' => C('status.req.success'),
                'role_list' => $role_list
        ) ;
        $this->_return_json($arr) ;
    }

    /**
     * 添加用户页面数据获取
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function create_input () {
        // 数据查询
        $where = array (
                'status' => 1
        ) ;
        // 角色列表
        $role_list = $this->MRole->get_lists('*', array (
                'status' => 1,
                'id !=' => C('user.superadmin.admin.type')
        )) ;
        $order = array (
                'path' => 'asc'
        ) ;
        // 部门列表
        $dept_list_tmp = $this->MDepartment->get_lists('*', array (
                'status' => 1
        ), $order) ;
        $dept_list = array () ;
        foreach ( $dept_list_tmp as $v ) {
            $v['pre_name'] = str_repeat('----', $v['level']) . $v['name'] ;
            $dept_list[] = $v ;
        }
        // 开放城市列表
        $open_city = C('city.open') ;
        $open_city = array_values($open_city) ;
        $open_city_ids = array () ;
        foreach ( $open_city as $v ) {
            $open_city_ids[] = $v ;
        }
        $province_list = $this->MLocation->get_lists('*', array (
                'upid' => 0
        )) ;

        // 返回结果
        $this->_return_json(array (
                'status' => C('status.req.success'),
                'dept_list' => $dept_list,
                'role_list' => $role_list,
                'province_list' => $province_list
        )) ;
    }

    /**
     * 添加用户
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function create () {
        // 表单校验
        $this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|numeric') ;
        $this->form_validation->set_rules('name', '姓名', 'trim|required') ;
        $this->form_validation->set_rules('roleId', '类型', 'required|numeric|greater_than[1]') ;
        $this->form_validation->set_rules('provinceId', '省份', 'required') ;
        $this->form_validation->set_rules('address', '详细地址', 'required') ;
        $this->validate_form() ;
        // 数据处理
        $data = $this->_deal_user_data() ;
        // 验证用户手机号是否唯一
        if (! $this->MUser->check_mobile_unique($data['mobile'])) {
            $this->_return_json(array (
                    'status' => C('status.req.failed'),
                    'msg' => '手机号已经被注册过，请更换其他手机号'
            )) ;
        }

        $password = $this->userauth->get_rand_pass() ;
        // 根据salt创建密码
        $this->_create_salt() ;
        $data['password'] = $this->create_password($password, $this->_salt) ;
        $data['salt'] = $this->_salt ;
        $data['created_time'] = $this->input->server("REQUEST_TIME") ;
        $data['max_customer'] = C('user.sale_config.max_customer');
        $data['max_potential_customer'] = C('user.sale_config.max_potential_customer');
        $data['customer_protect'] = C('user.sale_config.customer_protect');
        $data['potential_customer_protect'] = C('user.sale_config.potential_customer_protect');

        // 用户添加，入库
        if ($insert_id = $this->MUser->create($data)) {
            // 用户添加成功后根据用户的手机类型不同发送不同的短信
            $content = sprintf(C("register_msg.sms_pattern_crm"), $password, C('shortlink.crm')) ;

            $this->_return_json(array (
                    'status' => C('status.req.success'),
                    'msg' => '用户添加成功',
                    'content' => $content,
                    'info' => array (
                            'id' => $insert_id
                    )
            )) ;
        } else {
            $this->_return_json(array (
                    'status' => C('status.req.failed'),
                    'msg' => '用户添加失败'
            )) ;
        }
    }

    /**
     * 修改用户页面
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function edit_input () {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric') ;
        $this->validate_form() ;

        // 数据查询
        $where = array (
                'status' => 1
        ) ;
        // 数据处理
        $data = $this->MUser->get_one('*', array (
                'id' => $this->input->post('id', TRUE)
        )) ;
        // 角色列表
        $role_list = $this->MRole->get_lists('*', array (
                'status' => 1,
                //'id !=' => C('user.superadmin.admin.type')
        )) ;
        $order = array (
                'path' => 'asc'
        ) ;
        // 部门列表
        $dept_list_tmp = $this->MDepartment->get_lists('*', array (
                'status' => 1
        ), $order) ;
        $dept_list = array () ;
        foreach ( $dept_list_tmp as $v ) {
            $v['pre_name'] = str_repeat('----', $v['level']) . $v['name'] ;
            $dept_list[] = $v ;
        }
        // 开放城市列表
        $open_city = C('city.open') ;
        $open_city = array_values($open_city) ;
        $open_city_ids = array () ;
        foreach ( $open_city as $v ) {
            $open_city_ids[] = $v ;
        }
        $province_list = $this->MLocation->get_lists('*', array (
                'upid' => 0
        )) ;

        // 返回结果
        $this->_return_json(array (
                'status' => C('status.req.success'),
                'info' => $data,
                'dept_list' => $dept_list,
                'role_list' => $role_list,
                'province_list' => $province_list,
        )) ;
    }

    /**
     * 修改用户
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function edit () {
        $this->form_validation->set_rules('id', 'ID', 'required|numeric') ;
        $this->form_validation->set_rules('deptId', '部门', 'required|numeric') ;
        $this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|numeric') ;
        $this->form_validation->set_rules('name', '姓名', 'trim|required') ;
        $this->form_validation->set_rules('roleId', '类型', 'required|numeric|greater_than[1]') ;
        $this->form_validation->set_rules('provinceId', '省份', 'required') ;
        $this->form_validation->set_rules('address', '详细地址', 'required') ;
        $this->validate_form() ;

        $data = $this->_deal_user_data() ;
        $this->_check_edit($data);

        // 用户修改，入库
        $this->MUser->update_info($data, array (
                'id' => $_POST['id']
        )) ;
        $this->_return_json(array (
                'status' => C('status.req.success'),
                'msg' => '用户修改成功'
        )) ;
    }

    /**
     * 删除用户
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function delete () {
        // 表单校验
        $this->form_validation->set_rules('id', 'ID', 'required|numeric') ;
        $this->validate_form() ;

        $del_id = $this->input->post('id', TRUE) ;
        $where = array (
                'id' => $del_id
        ) ;
        // 假删除数据
        $result = $this->MUser->false_delete($where) ;
        $this->_return($result) ;
    }

    /**
     * 用户登录
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function login () {
        $is_logined = $this->userauth->current() ;
        if (! empty($is_logined)) {
            $this->userauth->logout() ;
        }
        // 表单数据校验
        $this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|numeric') ;
        $this->form_validation->set_rules('password', '密码', 'required') ;
        if ($this->form_validation->run() === FALSE) {
            $this->_return_json(array (
                    'status' => C("userauth.invalid_info.id"),
                    'msg' => C("userauth.invalid_info.msg")
            )) ;
        }

        $is_customer = TRUE;
        if (isset($_POST['login_type']) && 'user' == $_POST['login_type']) {
            $is_customer = FALSE;
        }
        $login_result = $this->userauth->login($_POST['mobile'], $_POST['password'], $is_customer);
        // 不允许供应商和采购商登录
        if (! empty($login_result['info']['type'])) {
            if (in_array($login_result['info']['type'], array (
                    C('user.normaluser.purchase.type'),
                    C('user.normaluser.supply.type')
            ))) {
                $this->userauth->logout() ;
                $this->_return_json(array (
                        'status' => C('tips.code.op_failed'),
                        'msg' => '没有权限访问'
                )) ;
            }
        }

        if (! empty($login_result)) {
            $this->_return_json($login_result) ;
        }

        $this->_return_json(array (
                'status' => C("userauth.default.id"),
                'msg' => C("userauth.default.msg")
        )) ;
    }

    /**
     * 获取用户个人中心的基本信息
     *
     * @author yugang@dachuwang.com
     * @version : 1.0.0
     * @since : 2015-03-03
     */
    public function baseinfo () {
        $post = $this->post ;
        $uinfo = $this->userauth->current() ;
        $res = array (
                'uinfo' => $uinfo
        ) ;
        // 采购商和供应商有财富信息
        if (in_array($uinfo['type'], array (
                C('user.normaluser.supply.type'),
                C('user.normaluser.purchase.type')
        ))) {
            $user_wealth = $this->MUser_wealth->get_one('*', array (
                    'user_id' => $uinfo['id']
            )) ;
            if ($user_wealth) {
                $user_wealth['wealth'] /= 100 ;
            }
            $res['wealth'] = $user_wealth ;
        }

        // 采购商，供应商，地推可以看订单信息
        // 地推只是看演示的订单信息
        $order_info = array () ;
        if (! in_array($uinfo['type'], array (
                C('user.superadmin.admin.type')
        ))) {
            $key = 'uid' ;
            if (in_array($uinfo['type'], array (
                    C('user.normaluser.supply.type')
            ))) {
                $key = 'supply_uid' ;
            }
            $order_info = $this->MOrder->get_info($uinfo['id'], $key) ;
            $res['order_info'] = $order_info ;
        }

        // 查看管理用户
        $invite_info = array () ;
        if (in_array($uinfo['type'], array (
                C('user.superadmin.admin.type'),
                C('user.admingroup.spreader.type')
        ))) {
            $count = $this->MUser->count(array (
                    'in' => array (
                            'type' => array (
                                    C('user.normaluser.supply.type'),
                                    C('user.normaluser.purchase.type')
                            )
                    ),
                    "invite_id" => $uinfo['id']
            )) ;
            $invite_info = array (
                    'name' => '总用户数',
                    'number' => $count,
                    'href' => '/admin/user'
            ) ;
            $res['invite_info'] = $invite_info ;
        }

        $this->_return_json(array (
                'status' => C('status.req.success'),
                'info' => $res
        )) ;
    }

    /**
     * 修改密码
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function change_password () {
        $cur = $_POST['info'] ;
        // 验证用户输入密码是否正确
        $password = $this->create_password($_POST['password'], $cur['salt']) ;
        if ($password != $cur['password']) {
            $this->_return_json(array (
                    'status' => FALSE,
                    'msg' => '密码输入错误，请重新输入'
            )) ;
        }
        $new_pass = $this->create_password($_POST['new_password'], $cur['salt']) ;
        if ($cur) {
            $data = array (
                    'password' => $new_pass
            ) ;
            $where = array (
                    'id' => $_POST['id']
            ) ;
            // 修改用户密码
            $result = $this->MUser->update_info($data, $where) ;
            $data = array (
                    'status' => C('tips.code.op_success'),
                    'msg' => '修改密码成功'
            ) ;
        } else {
            $data = array (
                    'status' => C('tips.code.op_failed'),
                    'msg' => '提交参数错误'
            ) ;
        }
        $this->_return_json($data) ;
    }

    /**
     * 获取个人信息
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function getinfo () {
        // 获取当前登录用户
        $cur = $this->userauth->current() ;
        // 返回用户编辑需要的相关资料
        $this->load->model('MLocation') ;
        $provinces = $this->MLocation->list_province() ;
        $citys = $this->MLocation->get_sons($cur['province_id']) ;
        $countys = $this->MLocation->get_sons($cur['city_id']) ;
        if (! empty($county_id)) {
            $towns = $this->MLocation->get_sons($cur['county_id']) ;
        }
        if (! empty($town_id)) {
            $streets = $this->MLocation->get_sons($cur['town_id']) ;
        }
        $data = array (
                'status' => C('status.common.success'),
                'type' => $cur['type'],
                'info' => $cur,
                'provinces' => $provinces,
                'citys' => $citys,
                'countys' => $countys
        ) ;
        $this->_return_json($data) ;
    }

    /**
     * 用户修改个人信息
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function edit_personal_info_input () {
        // 获取当前登录用户
        $cur = $this->userauth->current() ;

        // 返回用户编辑需要的相关资料
        $provinces = $this->MLocation->list_province() ;
        $citys = $this->MLocation->get_sons($cur['province_id']) ;
        $countys = $this->MLocation->get_sons($cur['city_id']) ;
        $data = array (
                'status' => C('status.req.success'),
                'role' => $cur['role_id'],
                'info' => $cur,
                'provinces' => $provinces,
                'citys' => $citys,
                'countys' => $countys
        ) ;

        // 返回结果
        $this->_return_json($data) ;
    }

    /**
     * 用户修改个人信息
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function edit_personal_info () {
        // 获取当前登录用户
        $cur = $this->userauth->current() ;
        // 数据校验
        $this->form_validation->set_rules('name', '姓名', 'trim|required') ;
        $this->form_validation->set_rules('roleId', '类型', 'required|greater_than[1]') ;
        $this->form_validation->set_rules('provinceId', '省份', 'required') ;
        $this->form_validation->set_rules('cityId', '城市', 'required') ;
        $this->form_validation->set_rules('address', '详细地址', 'required') ;
        $this->validate_form() ;

        // 数据处理
        $data = $this->_deal_user_data() ;
        // 用户修改，入库
        $this->MUser->update_info($data, array (
                'id' => $cur['id']
        )) ;

        // 返回结果
        $this->_return_json(array (
                'status' => C('status.common.success'),
                'role' => $cur['role_id'],
                'msg' => '用户资料修改成功'
        )) ;
    }

    public function user_type () {
        // 根据登陆的电话号码,获取此人是否可以添加的角色;
        $cur = $this->userauth->current() ;
        if ($cur && $cur['type'] == C('user.superadmin.admin.type')) {
            $user_type = C('user.admingroup') ;
        } else {
            $user_type = C('user.normaluser') ;
        }
        $res = array () ;
        foreach ( $user_type as $k => $v ) {
            $res[] = $v ;
        }
        $this->_return_json(array (
                'status' => C('status.req.success'),
                'user_type' => $res
        )) ;
    }

    /**
     * 重置密码
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function reset_password () {
        // 表单数据校验
        $this->form_validation->set_rules('uid', '用户ID', 'required|numeric') ;
        $this->validate_form() ;
        // 数据权限校验
        // $auth_uid = $this->MUser->get_auth_uid($this->input->post('uid', TRUE));
        // $this->check_dataset_validation('user', $auth_uid);
        $password = $this->userauth->get_rand_pass() ;
        $result = $this->MUser->reset_password($this->input->post('uid'), $password) ;
        $content = sprintf(C('register_msg.sms_resetpwd_hop'), $password) ;

        // 返回结果
        $this->_return_json(array (
                'status' => C('status.req.success'),
                'msg' => '密码重置成功',
                'content' => $content
        )) ;
    }

    /**
     * 退出
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    public function logout () {
        $this->userauth->logout() ;
        $this->_return_json(array (
                'status' => C('status.req.success'),
                'msg' => '退出成功'
        )) ;
    }


    /**
     * 禁用用户
     * @author yugang@dachuwang.com
     * @since 2015-08-08
     */
    public function disable() {
        // 表单数据校验
        $this->form_validation->set_rules('uid', '用户ID', 'required|numeric') ;
        $this->validate_form() ;

        // 禁用BD时需要判断该客户是否持有客户
        $count = $this->MCustomer->count(['invite_id' => $_POST['uid'], 'status >' => C('status.common.del')]);
        if ($count > 0) {
            $this->_return_json(
                [
                    'status' => C('status.req.failed'),
                    'msg'    => '该用户还持有客户，不允许禁用'
                ]
            );

        }

        $this->MUser->update_info(['status' => C('status.common.disabled')], ['id' => $_POST['uid']]);
        $this->_return(true);
    }


    /**
     * 启用用户
     * @author yugang@dachuwang.com
     * @since 2015-08-08
     */
    public function enable() {
        // 表单数据校验
        $this->form_validation->set_rules('uid', '用户ID', 'required|numeric') ;
        $this->validate_form() ;

        // 启用账号
        $this->MUser->update_info(['status' => C('status.common.normal')], ['id' => $_POST['uid']]);
        $this->_return(true);
    }


    /**
     * 创建密码 ription 创建密码
     *
     * @author yugang@dachuwang.com
     */
    protected function create_password ($str, $salt) {
        return md5(md5($str) . $salt) ;
    }

    /**
     * 创建盐
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    protected function _create_salt () {
        if (empty($this->_salt)) {
            $this->_salt = substr(md5(uniqid()), 0, 6) ;
        }
    }

    /**
     * 统计用户订单数量
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    private function _count_user_order ($user_list) {
        $res = array () ;
        $uids = array_column($user_list, 'uid') ;
        $user_orders = $this->MOrder->count_by_uids($uids) ;
        foreach ( $user_list as $v ) {
            $v['order'] = empty($user_orders[$v['uid']]) ? "0" : $user_orders[$v['uid']] ;
            $res[] = $v ;
        }

        return $res ;
    }

    /**
     * 处理表单中的数据 ription 将表单中的数据做处理，存入一个数组返回
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     * @return 处理后的数组
     */
    private function _deal_user_data () {
        $data['role_id'] = $this->input->post('roleId', TRUE) ;
        $data['dept_id'] = $this->input->post('deptId', TRUE) ;
        $data['name'] = $this->input->post('name', TRUE) ;
        $data['mobile'] = $this->input->post('mobile', TRUE) ;
        $data['province_id'] = $this->input->post('provinceId', TRUE) ;
        $data['address'] = $this->input->post('address', TRUE) ;
        $data['updated_time'] = $this->input->server("REQUEST_TIME") ;
        $data = array_filter($data) ;
        return $data ;
    }

    /**
     * 处理列表数据
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-03
     */
    private function _format_list ($list) {
        $result = array () ;
        $role_list = $this->MRole->get_lists('id, name') ;
        $dept_list = $this->MDepartment->get_lists('id, name') ;
        foreach ( $list as $k => $v ) {
            $v['created_time'] = date('Y-m-d H:i:s', $v['created_time']) ;
            foreach ( $role_list as $role ) {
                if ($role['id'] == $v['role_id']) {
                    $v['role'] = $role['name'] ;
                    break ;
                }
            }
            foreach ( $dept_list as $dept ) {
                if ($dept['id'] == $v['dept_id']) {
                    $v['dept'] = $dept['name'] ;
                }
            }
            $result[] = $v ;
        }

        return $result ;
    }

    /**
     * 检查编辑用户是否合法,用户角色由BD到AM互转或更改系统时需要判断用户的客户是否移交完成
     * @author yugang@dachuwang.com
     * @since 2015-05-07
     */
    private function _check_edit($data) {
        $user_id = $_POST['id'];
        $bd_array = array(C('user.saleuser.BD.type'));
        $src_data = $this->MUser->get_one('*', array('id' => $user_id));
        // 如果用户角色和城市未发生变化或者原来角色不是BD，则不做判断
        if (!in_array($src_data['role_id'], $sale_array) || ($src_data['role_id'] == $data['role_id'] && $src_data['province_id'] == $data['province_id'])) {
            return TRUE;
        }

        $where = array();
        $where['status >'] = C('status.common.del');
        $where['invite_id'] = $user_id;
        $count = $this->MCustomer->count($where);
        $count += $this->MPotential_customer->count($where);
        if($count > 0) {
            $this->_return_json(
                array(
                    'status' => C('status.req.failed'),
                    'msg'    => '该用户有未移交的客户，请移交完成后再更改客户状态',
                )
            );
        }
    }
}
/* End of file user.php */
/* Location: :./application/controllers/user.php */
