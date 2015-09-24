<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 *
 * @author maqiang
 *        
 */
class Customer_visit extends MY_Controller
{

    public static $user_info = [];

    public function __construct()
    {
        parent::__construct();
        $cur = $this->userauth->current(FALSE);
        if (empty($cur)) {
            $this->_return_json(array(
                'status' => C('status.auth.login_timeout'),
                'msg' => '登录超时，请重新登录'
            ));
        } else {
            self::$user_info = $cur;
        }
        $this->load->library(array(
            'form_validation'
        ));
    }

    /**
     * 获取列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function lists()
    {
        $user = self::$user_info;
        $type = $user['role_id'];
        if ((intval($type) != intval(C('role.BD.code'))) && (intval($type) != intval(C('role.BDM.code')))) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        if (intval($type)  == intval(C('role.BD.code'))) {
            $_POST['bd_id'] = $user['id'];
        }else{
            $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
            $this->validate_form();
        }
        
        $rejected_lists_response = $this->format_query('/customer_visit/lists', $_POST);
        $this->_return_json($rejected_lists_response);
    }

    /**
     * 拜访详情页
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function view()
    {
        $user = self::$user_info;
        $type = $user['role_id'];
        if ((intval($type) != intval(C('role.BD.code'))) && (intval($type) != intval(C('role.BDM.code')))) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
        $this->validate_form();
        $_POST['bd_id'] = $user['id'];
        $response = $this->format_query('/customer_visit/view', $_POST);
        $this->_return_json($response);
    }

    /**
     * 进店拜访页面
     *
     * @author maqiang@dachuwang.com
     * @since 2015-07-15
     */
    public function for_visit()
    {
        $user = self::$user_info;
        $type = $user['role_id'];
        if (intval($type) != intval(C('role.BD.code'))) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        $this->form_validation->set_rules('is_potential', 'is_potential', 'required|regex_math[/^[01]$/]');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        $this->validate_form();
        $_POST['bd_id'] = $user['id'];
        $response = $this->format_query('/customer_visit/for_visit', $_POST);
        $this->_return_json($response);
    }

    /**
     * 创建拜访
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function create()
    {
        $user = self::$user_info;
        $type = $user['role_id'];
        if (intval($type) != intval(C('role.BD.code'))) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        $this->form_validation->set_rules('is_potential', 'is_potential', 'required|regex_math[/^[01]$/]');
        $this->form_validation->set_rules('type', 'type', 'required|regex_match[/^[01]$/]');
        $this->form_validation->set_rules('user_id', 'user_id', 'required|integer');
        if (isset($_POST['type'])  && intval($_POST['type'])== 0) {
            $this->form_validation->set_rules('visit_date', 'visit_date', 'required|integer');
        } else {
            $this->form_validation->set_rules('focus_category', 'focus_category', 'required|regex_match[/^\d+(\,\d+)*$/]');
            $this->form_validation->set_rules('suggestion_type', 'suggestion_type', 'required|regex_match[/^\d+(\,\d+)*$/]');
            if (isset($_POST['remarks']) && $_POST['remarks'] != '') {
               $this->form_validation->set_rules('remarks', 'remarks', 'max_length[100]');
            }
        }
        $this->validate_form();
        $_POST['bd_id'] = $user['id'];
        $response = $this->format_query('/customer_visit/create', $_POST);
        $this->_return_json($response);
    }

    /**
     * 更新拜访
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function update()
    {
        $user = self::$user_info;
        $type = $user['role_id'];
        if (intval($type) != intval(C('role.BD.code'))) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
        $this->form_validation->set_rules('focus_category', 'focus_category', 'required|regex_match[/\d+(\,\d+)*/]');
        $this->form_validation->set_rules('suggestion_type', 'suggestion_type', 'required|regex_match[/\d+(\,\d+)*/]');
        if (isset($_POST['remarks']) && trim($_POST['remarks']) != '') {
            $this->form_validation->set_rules('remarks', 'remarks', 'required|max_length[100]');
        }
        $this->validate_form();
        $_POST['bd_id'] = $user['id'];
        $response = $this->format_query('/customer_visit/update', $_POST);
        $this->_return_json($response);
    }

   public function  del(){
       $user = self::$user_info;
       $type = $user['role_id'];
       if (intval($type) != intval(C('role.BD.code'))) {
           $this->_return_json(array(
               'status' => C('tips.code.op_failed'),
               'msg' => '没有权限访问'
           ));
       }
       $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
       $this->validate_form();
       $_POST['bd_id'] = $user['id'];
       $response = $this->format_query('/customer_visit/del', $_POST);
       $this->_return_json($response);
   }
    
    public function update_visit_date()
    {
        $user = self::$user_info;
        $type = $user['role_id'];
        if (intval($type) != intval(C('role.BD.code'))) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
        $this->form_validation->set_rules('visit_date', 'visit_date', 'required|integer|exact_length[10]');
        $this->validate_form();
        $_POST['bd_id'] = $user['id'];
        $response = $this->format_query('/customer_visit/update_visit_date', $_POST);
        $this->_return_json($response);
    }
    
    public function update_remarks()
    {
        $user = self::$user_info;
        $type = $user['role_id'];
        if (intval($type) != intval(C('role.BD.code'))) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        $this->form_validation->set_rules('visit_id', 'visit_id', 'required|integer');
        $this->form_validation->set_rules('remarks', 'remarks', 'required|max_length[100]');
        $this->validate_form();
        $_POST['bd_id'] = $user['id'];
        $response = $this->format_query('/customer_visit/update_remarks', $_POST);
        $this->_return_json($response);
    }
    
    /**
     * 拜访统计
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function statistics()
    {
        $user = self::$user_info;
        $type = $user['role_id'];
        if (intval($type) != intval(C('role.BDM.code'))) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
        
        $this->form_validation->set_rules('date_type', 'date_type', 'required|regex_match[/^[012]$/]');
        $this->validate_form();
        $_POST['bdm_id'] = $user['id'];
        $response = $this->format_query('/customer_visit/statistics', $_POST);
        $this->_return_json($response);
    }

    /**
     * 拜访日期
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function calendar()
    {
        $user = self::$user_info;
        $type = $user['role_id'];
        if (intval($type) == intval(C('role.BD.code'))) {
             $_POST['bd_id'] =  $user['id'];
        }else  if  (intval($type)  ==  intval(C('role.BDM.code'))){
            $this->form_validation->set_rules('bd_id', 'bd_id', 'required|integer');
            $this->validate_form();
       }else {
           $this->_return_json(array(
               'status' => C('tips.code.op_failed'),
               'msg' => '没有权限访问'
           ));
       }
        $response = $this->format_query('/customer_visit/calendar', $_POST);
        $this->_return_json($response);
    }
    
    /**
     * 获取bd的私海客户信息
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function  get_private_sea() {
        $user = self::$user_info;
        $type = $user['role_id'];
        if (intval($type) != intval(C('role.BD.code'))) {
            $this->_return_json(array(
                'status' => C('tips.code.op_failed'),
                'msg' => '没有权限访问'
            ));
        }
         
        $_POST['bd_id'] = $user['id'];
        $response = $this->format_query('/customer/get_private_sea_cutomer', $_POST);
        $this->_return_json($response);
    }
}
