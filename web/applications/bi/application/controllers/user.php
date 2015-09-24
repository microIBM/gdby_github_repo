<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * 用户操作
 * 
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-05
 */
class user extends CI_Controller {
    const WHITE_BI_MODULE = 1; //BI系统白名单模块ID

    protected $_salt = NULL;
    public $data = array();

    public function __construct() {
        parent::__construct();
        $this->load->library(array(
            'UserAuth',
            'Http'
        ));
        $this->load->model(array(
            'MUser'
        ));
        $this->load->library(array(
            'form_validation'
        ));

        $this->_service_url = C('service.s');
        $this->data['base_url'] = C('config.base_url');
        $this->data['img_url'] = C('config.img_url');
        $this->data['api_url'] = C('config.api_url');
        $this->data['web_url'] = C('config.web_url');
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    public function index() {

        $this->load->view('shared/login', $this->data);
    }

    /**
     * 用户注册
     * 
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function register() {
        
    }

    /**
     * 用户登陆
     * 
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function login() {
        $is_logined = $this->userauth->current(FALSE);
        if (!empty($is_logined)) {
            $this->userauth->logout(FALSE);
        }
        // 表单数据校验
        $this->form_validation->set_rules('mobile', '手机号', 'trim|required|exact_length[11]|numeric');
        $this->form_validation->set_rules('password', '密码', 'required');
        if ($this->form_validation->run() === FALSE) {
            $this->_return_json(array(
                'status' => C("userauth.invalid_info.id"),
                'msg' => C("userauth.invalid_info.msg")
            ));
        }
        //验证当前用户是否拥有当前系统的白名单
        $check_info = $this->format_query('white_user/check_white_user', array('module_id' => self::WHITE_BI_MODULE, 'mobile' => $_POST['mobile']));
        if (0 !== $check_info['status']) {
            $this->_return_json(
                    array(
                        'status' => C('tips.code.op_failed'),
                        'msg' => '你的账号没有权限访问'
                    )
            );
        }

        $login_result = $this->userauth->login($_POST['mobile'], $_POST['password'], FALSE);
        if (!empty($login_result['info']['type'])) {
            if (in_array($login_result['info']['type'], array(
                        C('user.normaluser.purchase.type'),
                        C('user.normaluser.supply.type')
                    ))) {
                $this->userauth->logout();
                $this->_return_json(array(
                    'status' => C('tips.code.op_failed'),
                    'msg' => '没有权限访问'
                ));
            }
        }

        if (!empty($login_result)) {
            $this->_return_json($login_result);
        }

        $this->_return_json(array(
            'status' => C("userauth.default.id"),
            'msg' => C("userauth.default.msg")
        ));
    }

    /**
     * 用户退出
     * 
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function logout() {
        $this->userauth->logout(FALSE);
        header('location:' . $this->data['base_url'] . '/user');
    }

    /**
     * ription
     * 
     * @author : liaoxianwen@ymt360.com
     * @param
     *        	: array arr 需要转成json的数组
     */
    public function _return_json($arr) {
        if (in_array($this->input->server("HTTP_ORIGIN"), C("allowed_origins"))) {
            header('Access-Control-Allow-Origin: ' . $this->input->server("HTTP_ORIGIN"));
        } else {
            header('Access-Control-Allow-Origin: http://www.dachuwang.com');
        }
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: X-Requested-With');
        header('Cache-Control: no-cache');
        echo json_encode($arr);
        exit();
    }

    /**
     * ajax请求通用返回接口
     * 
     * @author : yugang@ymt360.com
     * @param
     *        	: result 操作结果
     * @since 2015-01-27
     */
    public function _return($result, $success_msg = '操作成功', $failure_msg = '操作失败') {
        if ($result) {
            $this->_return_json(array(
                'status' => C('status.req.success'),
                'msg' => $success_msg
            ));
        } else {
            $this->_return_json(array(
                'status' => C('status.req.failed'),
                'msg' => $failure_msg
            ));
        }
    }

    /**
     * 进行权限验证
     * 
     * @author yugang@ymt360.com
     * @since 2015-01-23
     */
    public function check_validation($resource, $operation, $module = '', $is_customer = TRUE) {
        $result = $this->userauth->check_validation($resource, $operation, $module, $is_customer);
        if ($result == C('status.auth.login_timeout')) {
            $this->_return_json(array(
                'status' => C('status.auth.login_timeout'),
                'msg' => '登录超时，请重新登录'
            ));
        } elseif ($result == C('status.auth.forbidden')) {
            header('HTTP/1.0 403 Forbidden');
            echo 'You are forbidden!';
            exit();
        }
    }

    /**
     * 进行表单验证
     * ription 进行表单验证，如果失败返回错误提示
     * 
     * @author yugang@ymt360.com
     */
    public function validate_form() {
        if ($this->form_validation->run() === FALSE) {
            $this->_return_json(array(
                'status' => C('status.req.invalid'),
                'msg' => '请填写完整必填的信息'  // 表单验证错误提示信息validation_errors()
            ));
        }
    }

    /**
     * ription 格式化数据
     * 
     * @author : liaoxianwen@dachuwang.com
     */
    public function format_query($uri_string, $data = array()) {
        $url = $this->_service_url . '/' . $uri_string;
        $return_data = $this->http->query($url, $data);

        return json_decode($return_data, TRUE);
    }

}

/* End of file user.php */
/* Location: :./application/controllers/user.php */
