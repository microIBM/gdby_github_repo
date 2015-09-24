<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 客户操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-05
 */
class Customer extends MY_Controller {
    protected $_salt  = NULL;
    private $_notify_lists = [
        'address' => '地址',
        'dimensions' => '店铺规模',
        'direction' => '店铺方位',
        'lat' => '定位',
        'lng' => '定位'
    ];
    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MLocation',
                'MCustomer',
                'MPhone',
                'MOrder',
            )
        );
        $this->load->library(
            array(
                'form_validation',
                'phone',
            )
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 客户列表
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function lists() {
        $cur = $this->_check_login();
        if(!isset($_POST['invite_id'])){
            $_POST['invite_id'] = $cur['id'];
        }
        // 调用基础服务接口
        $return = $this->format_query('/customer/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 云图客户列表
     * @author yugang@dachuwang.com
     * @since 2015-05-26
     */
    public function nearby_lists() {
        $cur = $this->_check_login();
        $list = [];
        $max_result = intval(C('customer.cloudmap.max_result'));
        $_POST['itemsPerPage'] = 'all';
        $_POST['provinceId'] = $cur['province_id'];
        $_POST['siteId'] = $cur['site_id'];
        // 根据查询类型的不同分别查询并合并查询结果
        if(isset($_POST['type']) && is_array($_POST['type'])) {
            $type = $_POST['type'];
            $return = [];
            foreach ($type as $v) {
                switch ($v){
                case C('customer.cloudmap.ctype.my_potential_customer.value') :
                    $query = $_POST;
                    $query['invite_id'] = $cur['id'];
                    $return = $this->format_query('/potential_customer/lists', $query);
                    break;
                case C('customer.cloudmap.ctype.other_potential_customer.value') :
                    $query = $_POST;
                    $query['not_invite_id'] = [$cur['id'], C('customer.public_sea_code')];
                    $return = $this->format_query('/potential_customer/lists', $query);
                    break;
                case C('customer.cloudmap.ctype.my_customer.value') :
                    $query = $_POST;
                    $query['invite_id'] = $cur['id'];
                    $return = $this->format_query('/customer/lists', $query);
                    break;
                case C('customer.cloudmap.ctype.other_customer.value') :
                    $query = $_POST;
                    $query['not_invite_id'] = [$cur['id'], C('customer.public_sea_code')];
                    $return = $this->format_query('/customer/lists', $query);
                    break;
                case C('customer.cloudmap.ctype.open_potential_customer.value') :
                    $query = $_POST;
                    $query['invite_id'] = C('customer.public_sea_code');
                    $return = $this->format_query('/potential_customer/lists', $query);
                    break;
                case C('customer.cloudmap.ctype.open_customer.value') :
                    $query = $_POST;
                    $query['invite_id'] = C('customer.public_sea_code');
                    $return = $this->format_query('/customer/lists', $query);
                    break;
                }
                if(!empty($return['list'])){
                    foreach ($return['list'] as $item) {
                        $item['type'] = $v;
                        $list[] = $item;
                    }
                }
                $return = [];
            }
        }else{
            $_POST['itemsPerPage'] = $max_result;
            $return = $this->format_query('/customer/lists', $_POST);
            if(!empty($return['list'])){
                foreach ($return['list'] as $item) {
                    $item['type'] = $item['invite_id'] == $cur['id'] ? C('customer.cloudmap.ctype.my_customer.value') : C('customer.cloudmap.ctype.other_customer.value');
                    $list[] = $item;
                }
            }
            $return = $this->format_query('/potential_customer/lists', $_POST);
            if(!empty($return['list'])){
                foreach ($return['list'] as $item) {
                    $item['type'] = $item['invite_id'] == $cur['id'] ? C('customer.cloudmap.ctype.my_potential_customer.value') : C('customer.cloudmap.ctype.other_potential_customer.value');
                    $list[] = $item;
                }
            }

        }

        $return_list = [];
        if(!empty($list)){
            if(count($list) > $max_result && !empty($_POST['title'])) {
                $list = array_slice($list, 0, $max_result);
            }
            foreach ($list as $item) {
                $customer = [];
                $customer['id'] = $item['id'];
                $customer['owner'] = isset($item['name']) ? $item['name'] : '';
                $customer['BDName'] = !empty($item['sale']['name']) ? $item['sale']['name'] : '';
                $customer['BDMobile'] = !empty($item['sale']['mobile']) ? $item['sale']['mobile'] : '';
                $customer['longtitude'] = floatval($item['lng']);
                $customer['latitude'] = floatval($item['lat']);
                // $customer['phoneNum'] = $item['mobile'];
                $customer['title'] = $item['shop_name'];
                $customer['type'] = [intval($item['type'])];
                $customer['address'] = $item['address'];
                $return_list[] = $customer;
            }
        }

        $result['status'] = C('status.req.success');
        $result['list'] = $return_list;
        $this->_return_json($result);
    }

    /**
     * 返回BD经理部门下BD客户数量
     * @author yugang@dachuwang.com
     * @since 2015-03-20
     */
    public function list_group() {
        $cur = $this->_check_login();
        $_POST['user_id'] = $cur['id'];
        // 调用基础服务接口
        $return = $this->format_query('/customer/list_group', $_POST);
        $this->_return_json($return);
    }

    private function _check_notify($arr) {
        if($arr === NULL) {
            return NULL;
        }
        if(!is_array($arr['list'])) {
            return $arr;
        }
        /*
         * $arr包含了一系列客户的信息
         * $_notify_lists中的项是必填项需要若不全需要提示补全
         * 若某客户有不全的项则新增一个lack关键字，保存提示内容
         */
        foreach($arr['list'] as &$each_customer) {
            if(is_array($each_customer)) {
                foreach($this->_notify_lists as $key => $val) {
                    if(array_key_exists($key, $each_customer) && !$each_customer[$key]) {
                        $each_customer['lack'] = $val;
                    }
                }
            }
        }

        return $arr;
    }

    /**
     * BD的新注册客户
     * @author yugang@dachuwang.com
     * @since 2015-04-28
     */
    public function new_register_lists() {
        $cur = $this->_check_login();
        if(!isset($_POST['invite_id'])){
            $_POST['invite_id'] = $cur['id'];
        }
        // 调用基础服务接口
        $return = $this->format_query('/customer/new_register_lists', $_POST);
        $return = $this->_check_notify($return);
        $this->_return_json($return);
    }

    private function returnInterfaceError($msg=null) {
        $error = $msg ?: '接口调用失败';
        $this->_return_json([
            'status' => C('status.req.failed'),
            'msg' => $error
        ]);
    }

    public function register_lists() {
        $cur = $this->_check_login();
        if(!isset($_POST['invite_id'])) {
            $_POST['invite_id'] = $cur['id'];
        }
        $return = $this->format_query('/customer/'.__FUNCTION__, $_POST);
        if($return === null) {
            $this->returnInterfaceError();
        }
        $return = $this->_check_notify($return);
        $this->_return_json($return);
    }

    /**
     * BD已经下单但未完成的订单列表
     * @author yugang@dachuwang.com
     * @since 2015-04-28
     */
    public function undone_lists() {
        $cur = $this->_check_login();
        if(!isset($_POST['invite_id'])){
            $_POST['invite_id'] = $cur['id'];
        }
        // 调用基础服务接口
        $return = $this->format_query('/customer/undone_lists', $_POST);
        if($return === null) {
            $this->returnInterfaceError();
        }
        $return = $this->_check_notify($return);
        $this->_return_json($return);
    }

    /**
     * AM的用户列表
     * @author yugang@dachuwang.com
     * @since 2015-04-28
     */
    public function after_sale_lists() {
        $cur = $this->_check_login();
        if(!isset($_POST['invite_id'])){
            $_POST['invite_id'] = $cur['id'];
        }
        // 调用基础服务接口
        $return = $this->format_query('/customer/after_sale_lists', $_POST);
        $return = $this->_check_notify($return);
        $this->_return_json($return);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 更新用户密码
     */
    public function update_password() {
        // 表单校验
        $cur = $this->_check_login();
        // 需要更新uid
        $set['invite_id'] = $cur['id'];
        $set['uid'] = $_POST['uid'];
        $set['password'] = $this->_get_rand_pass();
        $return = $this->format_query('/customer/reset_password', $set);

        if(intval($return['status']) === 0) {
            $sms_return = $this->format_query('/sms/send_captcha',
                array('site' => $return['site'] ,'content' => $return['content'], 'mobile' => $return['mobile'])
            );
            unset($return['site']);
            unset($return['mobile']);
           // unset($return['content']);
        }

        $this->_return_json($return);
    }

    private function _get_rand_pass() {
        $rand_pass = str_split('abcdefghjkmnpqrst');
        shuffle($rand_pass);
        $pass_one = substr(implode("", $rand_pass), 5,3);
        $rand_pass = str_split('123456789');
        shuffle($rand_pass);
        $pass_one .= substr(implode("", $rand_pass), 5,3);
        $shuffle_arr = str_split($pass_one);
        shuffle($shuffle_arr);

        return implode("", $shuffle_arr);
    }


    /**
     * 查看客户
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function view() {
        $this->_check_login();
        // 调用基础服务接口
        $return = $this->format_query('/customer/view', $_POST);
        $this->_return_json($return);
    }

    /**
     * 添加客户输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create_input() {
        // 调用基础服务接口
        $return = $this->format_query('/customer/create_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 添加客户
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create() {
        $cur = $this->_check_login();
        // 调用基础服务接口
        $_POST['invite_id'] = $cur['id'];
        $_POST['siteId'] = $_POST['id'];
        $return = $this->format_query('/customer/create', $_POST);
        if(intval($return['status']) === 0) {
            $sms_return = $this->format_query('/sms/send_captcha',
                array('site' => $return['site'] ,'content' => $return['content'], 'mobile' => $_POST['mobile'])
            );
            unset($return['site']);
            unset($return['content']);
        }
        $this->_return_json($return);
    }

    /**
     * 编辑客户输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit_input() {
        // 调用基础服务接口
        $return = $this->format_query('/customer/edit_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑客户
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit() {
        // 调用基础服务接口
        $return = $this->format_query('/customer/edit', $_POST);
        $this->_return_json($return);
    }

    public function set_status() {
        // 重置状态
        $this->form_validation->set_rules('uid', '客户ID', 'required|numeric');
        $this->validate_form();
        $cur = $this->_check_login();
        $return_data = $this->format_query('/customer/set_status',
            array(
                'where' => array(
                    'id' => $this->post['uid'],
                    'invite_id' => $cur['id']
                ),
                'status' => $this->post['status']
            )
        );
        $this->_return_json($return_data);
    }

    private function _check_login() {
        $cur = $this->userauth->current(FALSE);
        if(!$cur) {
            $this->_return_json(
                array(
                    'status' => -100,
                    'msg' => '超时或还没有登录'
                )
            );
        }
        return $cur;
    }
}

/* End of file customer.php */
/* Location: :./application/controllers/customer.php */
