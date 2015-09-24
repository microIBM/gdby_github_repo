<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 权限操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-05
 */
class Privilege extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MPrivilege',
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
     * 权限列表
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function lists() {
        // 权限校验
        $this->check_validation('privilege', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/privilege/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 查看权限
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function view() {
        // 权限校验
        $this->check_validation('privilege', 'view', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/privilege/view', $_POST);
        $this->_return_json($return);

    }

    /**
     * 添加权限输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create_input() {
        // 权限校验
        $this->check_validation('privilege', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/privilege/create_input', $_POST);
        $this->_return_json($return);

    }

    /**
     * 添加权限
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function create() {
        // 权限校验
        $this->check_validation('privilege', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/privilege/create', $_POST);
        $this->_return_json($return);

    }

    /**
     * 编辑权限输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit_input() {
        // 权限校验
        $this->check_validation('privilege', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/privilege/edit_input', $_POST);
        $this->_return_json($return);

    }

    /**
     * 编辑权限
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function edit() {
        // 权限校验
        $this->check_validation('privilege', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/privilege/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 删除权限
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function delete() {
        // 权限校验
        $this->check_validation('privilege', 'delete', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/privilege/delete', $_POST);
        $this->_return_json($return);
    }

}

/* End of file privilege.php */
/* Location: :./application/controllers/privilege.php */
