<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 角色操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-05
 */
class Role extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MRole',
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
     * 角色列表
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function lists() {
        // 权限校验
        $this->check_validation('role', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/role/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 查看角色
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function view() {
        // 权限校验
        $this->check_validation('role', 'view', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/role/view', $_POST);
        $this->_return_json($return);
    }

    /**
     * 添加角色输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create_input() {
        // 权限校验
        $this->check_validation('role', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/role/create_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 添加角色
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create() {
        // 权限校验
        $this->check_validation('role', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/role/create', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑角色输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit_input() {
        // 权限校验
        $this->check_validation('role', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/role/edit_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑角色
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit() {
        // 权限校验
        $this->check_validation('role', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/role/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 删除角色
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function delete() {
        // 权限校验
        $this->check_validation('role', 'delete', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/role/delete', $_POST);
        $this->_return_json($return);
    }

}

/* End of file role.php */
/* Location: :./application/controllers/role.php */
