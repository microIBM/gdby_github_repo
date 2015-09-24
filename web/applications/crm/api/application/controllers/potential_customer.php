<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 潜在潜在客户操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-24
 */
class Potential_customer extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MPotential_customer',
            )
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 潜在客户列表
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function lists() {
        $cur = $this->_check_login();
        $_POST['province_id'] = $cur['province_id'];
        if(empty($_POST['invite_id'])) {
            // 如果不传invite_id,则返回当前登录用户添加的潜在客户
            $_POST['invite_id'] = $cur['id'];
        }
        // 调用基础服务接口
        $return = $this->format_query('/potential_customer/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 查看潜在客户
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function view() {
        $this->_check_login();
        // 调用基础服务接口
        $return = $this->format_query('/potential_customer/view', $_POST);
        $this->_return_json($return);
    }

    /**
     * 添加潜在客户输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function create_input() {
        // 调用基础服务接口
        $return = $this->format_query('/potential_customer/create_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 添加潜在客户
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function create() {
        $cur = $this->_check_login();
        // 判断私海潜在客户数量是否超过限制
        $max_potential_customer = $cur['max_potential_customer'];
        $cur_max = $this->MPotential_customer->count(['invite_id' => $cur['id'], 'status >' => C('status.common.del')]);
        if($cur_max >= $max_potential_customer){
            $this->_return_json([
                'status' => C('status.req.failed'),
                'msg'    => '私海潜在客户已经达到上限，不能继续添加！'
            ]);
        }
        // 调用基础服务接口
        $_POST['invite_id'] = $cur['id'];
        $_POST['invite_bd'] = $cur['id'];
        $return = $this->format_query('/potential_customer/create', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑潜在客户输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function edit_input() {
        // 调用基础服务接口
        $return = $this->format_query('/potential_customer/edit_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑潜在客户
     * @author yugang@dachuwang.com
     * @since 2015-03-24
     */
    public function edit() {
        // 调用基础服务接口
        $return = $this->format_query('/potential_customer/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 删除潜在客户
     * @author yugang@dachuwang.com
     * @since 2015-03-25
     */
    public function delete() {
        // 调用基础服务接口
        $return = $this->format_query('/potential_customer/delete', $_POST);
        $this->_return_json($return);
    }

    /**
     * 开通潜在客户
     * @author yugang@dachuwang.com
     * @since 2015-03-25
     */
    public function enable() {
        // 调用基础服务接口
        $cur = $this->_check_login();
        // 判断私海新注册客户数量是否已达到上限
        $max_customer = $cur['max_customer'];
        $cur_max = $this->MCustomer->count(['invite_id' => $cur['id'], 'status' => C('customer.status.new.code')]);
        if($cur_max >= $max_customer){
            $this->_return_json([
                'status' => C('status.req.failed'),
                'msg'    => '私海新注册客户已经达到上限，不能继续添加！'
            ]);
        }
        // 1.删除潜在客户
        $return = $this->format_query('/potential_customer/delete', $_POST);
        // 2.添加客户
        $pc = $this->MPotential_customer->get_one('invite_id', array('id' => $_POST['id']));
        // 开通潜在客户时，谁开通的就算是谁的
        $_POST['invite_id'] = $cur['id'];
        $_POST['invite_bd'] = $cur['id'];
        $return = $this->format_query('/customer/create', $_POST);
        // 3.发生短信
        if(intval($return['status']) === 0) {
            $sms_return = $this->format_query('/sms/send_captcha',
                array('site' => C('site.dachu'),'content' => $return['content'], 'mobile' => $_POST['mobile'])
            );
            unset($return['content']);
        }
        $this->_return_json($return);
    }

    // 检测是否登录
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

/* End of file potential_customer.php */
/* Location: :./application/controllers/potential_customer.php */
