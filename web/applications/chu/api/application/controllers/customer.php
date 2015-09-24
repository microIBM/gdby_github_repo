<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 客户操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-05
 */
class Customer extends MY_Controller {
    protected $_salt  = NULL;

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MProduct',
                'MLocation',
                'MCustomer',
                'MPhone',
                'MOrder',
                'MDeliver_fee',
            )
        );
        $this->load->library(
            array(
                'form_validation',
                'product_lib',
                'product_price'
            )
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 客户注册
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function register() {

    }

    /**
     * @author caochunhui@dachuwang.com
     */
    public function get_deliver_fee_rule($city_id = 0, $site_id = 0) {
        $res = [
            'free_amount' => 0,
            'fee' => 20
        ];

        if($city_id > 0 && $site_id > 0) {
            $rule = $this->MDeliver_fee->get_one(
                'free_amount, fee',
                array(
                    'city_id' => $city_id,
                    'site_id' => $site_id,
                    'status' => 1
                )
            );
            if(!empty($rule)) {
                $res['free_amount'] = $rule['free_amount'] / 100;
                $res['fee'] = $rule['fee'] / 100;
            }
        }

        return $res;
    }

    /**
     * 客户登陆
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function login() {
        $is_logined = $this->userauth->current(TRUE);
        if( !empty($is_logined) ) {
            $this->userauth->logout();
        }

        // 调用基础服务接口
        /*$return = $this->format_query('/customer/login', $_POST);
        $this->_return_json($return);*/
        // 表单校验
        $this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|numeric');
        $this->form_validation->set_rules('password', '密码', 'required');
        $this->validate_form();

        $login_result = $this->userauth->login($_POST['mobile'], $_POST['password'], TRUE);

        if(!empty($login_result)) {
            //获取运费规则
            $city_id          = isset($login_result['info']['city_id']) ? $login_result['info']['city_id'] : 0;
            $site_id          = isset($login_result['info']['site_id']) ? $login_result['info']['site_id'] : 0;
            $customer_type    = isset($login_result['info']['customer_type']) ? $login_result['info']['customer_type'] : C('customer.type.normal.value');
            $deliver_fee_rule = $this->get_deliver_fee_rule($city_id, $site_id);
            $login_result['deliver_fee_rule'] = $deliver_fee_rule;
            $login_result['customer_type'] = $customer_type;
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
     * 获取个人信息
     * @author yugang@dachuwang.com
     * @since 2015-03-06
     */
    public function baseinfo() {
        // 权限校验
        $this->check_validation('customer', 'view');
        // 获取当前登录客户
        $cur = $this->userauth->current(TRUE);
        
        $_POST['user_id'] = $cur['id'];

        // 调用基础服务接口,通过调用基础服务无法获取到对应的session信息
        $return = $this->format_query('/customer/baseinfo', $_POST);
        $valid_coupon_nums = $this->format_query('/customer_coupon/count', array('status' => C('coupon_status.valid.value'), 'customer_id' => $cur['id']));
        $return['valid_coupon_nums'] = isset($valid_coupon_nums['total']) ? $valid_coupon_nums['total'] : 0;
        $this->_return_json($return);
    }
    /**
     * 获取当前登陆客户的信息
     * @return json
     * @author yuanxiaolin@dachuwang.com
     */
    public function loginfo(){
        $data['status'] = C('status.req.success');
        $current = $this->userauth->current(TRUE);
        if (empty($current)) {
            $data['status'] = C('status.req.failed');
            $data['msg'] = '没有获取到用户登陆信息';
        }
        $data['info'] = $current;
        $this->_return_json($data);
    }
    
    /**
     * 客户退出
     * @author yugang@dachuwang.com
     * @since 2015-03-05
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
     * 修改密码
     * @author yugang@dachuwang.com
     * @since 2015-03-09
     */
    public function change_password() {
        // 获取当前登录客户
        $cur = $this->userauth->current(TRUE);
        $_POST['cur'] = $cur;
        // 调用基础服务接口
        $return = $this->format_query('/customer/change_password', $_POST);
        $this->_return_json($return);
    }

    /**
     * 查看客户
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function view() {
        // 调用基础服务接口
        $return = $this->format_query('/customer/view', $_POST);
        $this->_return_json($return);
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 经常购买的接口
     */
    public function always_buy_products() {
        $page = $this->get_page();
        $cur = $this->userauth->current(TRUE);
        $response = array(
            'status' => C('status.auth.login_timeout'),
            'msg'    => '登录超时，请重新登录'
        );
        !$cur AND $this->_return_json($response);
        $customer_type = C('customer.type.normal.value');
        $response = $this->format_query('/product/get_always_buy_products',
            array(
                'customer_type' => $customer_type,
                'location_id' => $cur['province_id'],
                'user_id' => $cur['id'],
                'currentPage' => $page['page'],
                'itemsPerPage' => $page['page_size']
            )
        );
        if(!empty($response['list'])) {
            $response['list'] = $this->product_price->get_rebate_price($response['list'], $cur['id'], FALSE);
            $product_list = $this->product_lib->set_product_fields($response['list']);
            $check_storage_info = $this->format_query('/stock_service/check_storage', array('products' => $product_list, 'line_id' => $cur['line_id']));
            $this->product_lib->set_default_check_storage_list($check_storage_info, $response['list']);
            $response['list'] = $this->product_lib->format_shop_product_list($response['list']);
        }
        $this->_return_json($response);
    }

    /**
     * @author changshaoshuai@dachuwang.com
     * @description 子账号列表
     */
    public function sub_account_list() {
        $tips = C('tips.code');
        $cur = $this->userauth->current(TRUE);
        if(empty($cur['id'])) {
            $this->_return_json(array('status' => $tips['op_success'], 'msg' => '还未登录!'));
        }
        if($cur['account_type'] == C('customer.account_type.child.value')) {
            $this->_return_json(array('status' => $tips['op_success'], 'msg' => '该账号是子账号!'));
        }
        $post = array(
            'id' => $cur['id'],
            'currentPage' => isset($_POST['currentPage']) ? $_POST['currentPage'] : 1,
            'itemsPerPage'=> isset($_POST['itemsPerPage']) ? $_POST['itemsPerPage'] : 10
        );
        $response = $this->format_query('/customer/sub_account_address', $post);
        $this->_return_json(
            array(
                'status' => $tips['op_success'],
                'list'   => isset($response['list']) ? $response['list'] : []
            )
        );
    }

    /**
     * 微信签名
     */
    public function wx_sign() {
        $this->load->library('weixin');
        $return = $this->weixin->wx_sign();
        $this->_return_json($return);
    }

}

/* End of file customer.php */
/* Location: :./application/controllers/customer.php */
