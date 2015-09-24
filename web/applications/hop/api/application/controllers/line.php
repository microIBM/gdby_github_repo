<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 线路操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-03-23
 */
class Line extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MLine',
                'MLocation',
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
     * 线路列表筛选项
     * @author yugang@dachuwang.com
     * @since 2015-05-28
     */
    public function list_options() {
        // 权限校验
        $this->check_validation('line', 'list', '', FALSE);
        // 调用基础服务接口
        $return['status'] = C('status.req.success');
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => 1
            )
        );
        $return['cities'] = $cities;
        //$site = C('site.code');
        //$return['sites'] = array_values($site);

        $this->_return_json($return);
    }

    /**
     * 线路列表
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function lists() {
        // 权限校验
        $this->check_validation('line', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/line/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 查看线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function view() {
        // 权限校验
        $this->check_validation('line', 'view', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/line/view', $_POST);
        $this->_return_json($return);
    }

    /**
     * 添加线路输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function create_input() {
        // 权限校验
        $this->check_validation('line', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/line/create_input', $_POST);
        // 调用odoo接口获取仓库列表
        $return_data = $this->http->query(C('service.api') . '/odoo_stock/get_warehouse', array());
        $return_data = json_decode($return_data, TRUE);
        $return['warehouses'] = $return_data['data'];
        $this->_return_json($return);
    }

    /**
     * 添加线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function create() {
        // 权限校验
        $this->check_validation('line', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/line/create', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑线路输入页面
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function edit_input() {
        // 权限校验
        $this->check_validation('line', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/line/edit_input', $_POST);
        // 调用odoo接口获取仓库列表
        $return_data = $this->http->query(C('service.api') . '/odoo_stock/get_warehouse', array());
        $return_data = json_decode($return_data, TRUE);
        $return['warehouses'] = $return_data['data'];
        $this->_return_json($return);
    }

    /**
     * 编辑线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function edit() {
        // 权限校验
        $this->check_validation('line', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/line/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 删除线路
     * @author yugang@dachuwang.com
     * @since 2015-03-23
     */
    public function delete() {
        // 权限校验
        $this->check_validation('line', 'delete', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/line/delete', $_POST);
        $this->_return_json($return);
    }

}

/* End of file line.php */
/* Location: :./application/controllers/line.php */
