<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 部门操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-05
 */
class Department extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MDepartment',
            )
        );
        $this->load->library(
            array(
                'form_validation',
            )
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 部门列表
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function lists() {
        // 权限校验
        $this->check_validation('department', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/department/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 查看部门
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function view() {

    }

    /**
     * 添加部门输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create_input() {
        // 权限校验
        $this->check_validation('department', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/department/create_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 添加部门
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create() {
        // 权限校验
        $this->check_validation('department', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/department/create', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑部门输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit_input() {
        // 权限校验
        $this->check_validation('department', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/department/edit_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑部门
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit() {
        // 权限校验
        $this->check_validation('department', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/department/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 删除部门
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function delete() {
        // 权限校验
        $this->check_validation('department', 'delete', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/department/delete', $_POST);
        $this->_return_json($return);
    }
}

/* End of file department.php */
/* Location: :./application/controllers/department.php */
