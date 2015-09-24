<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 咨询单操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-05-22
 */
class Consult extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MConsult',
                'MLocation',
            )
        );
        $this->load->library(
            array(
                'form_validation',
                'excel_export',
            )
        );
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 选项列表
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function list_options() {
        // 权限校验
        $this->check_validation('consult', 'list', '', FALSE);
        // 调用基础服务接口
        $return['status'] = C('status.req.success');
        $ctype = C('consult.ctype');
        $return['ctypes'] = array_values($ctype);
        $cstatus = array_values(C('consult.status'));
        $return['statuses'] = $cstatus;
        $operators = $this->MUser->get_lists('id, name', ['role_id' => C('user.admingroup.operator.type'), 'status' => C('status.common.normal')]);
        $return['operators'] = $operators;
        $this->_return_json($return);
    }

    /**
     * 咨询单列表
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function lists() {
        // 权限校验
        $this->check_validation('consult', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/consult/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 查看咨询单
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function view() {

    }

    /**
     * 添加咨询单输入页面
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function create_input() {
        // 权限校验
        $this->check_validation('consult', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/consult/create_input', $_POST);
        $cur = $this->userauth->current(false);
        $return['cur_name'] = $cur['name'];
        $this->_return_json($return);
    }

    /**
     * 添加咨询单
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function create() {
        // 权限校验
        $this->check_validation('consult', 'create', '', FALSE);
        $cur = $this->userauth->current(FALSE);

        // 数据处理
        $_POST['creator_id'] = $cur['id'];
        $_POST['creator'] = $cur['name'];

        // 调用基础服务接口
        $return = $this->format_query('/consult/create', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑咨询单输入页面
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function edit_input() {
        // 权限校验
        $this->check_validation('consult', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/consult/edit_input', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑咨询单
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function edit() {
        // 权限校验
        $this->check_validation('consult', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/consult/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 删除咨询单
     * @author yugang@dachuwang.com
     * @since 2015-05-22
     */
    public function delete() {
        // 权限校验
        $this->check_validation('consult', 'delete', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/consult/delete', $_POST);
        $this->_return_json($return);
    }

    /**
     * 导出咨询单
     * @author yugang@dachuwang.com
     * @since 2015-05-12
     */
    public function export() {
        // 权限校验
        $this->check_validation('consult', 'list', '', FALSE);
        $_POST['ids'] = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : '';
        $_POST['searchValue'] = isset($_REQUEST['searchValue']) ? $_REQUEST['searchValue'] : '';
        $_POST['status'] = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
        $_POST['operator'] = isset($_REQUEST['operator']) ? $_REQUEST['operator'] : '';
        $_POST['ctype'] = isset($_REQUEST['ctype']) ? $_REQUEST['ctype'] : '';
        $_POST['startTime'] = isset($_REQUEST['startTime']) ? $_REQUEST['startTime'] : '';
        $_POST['endTime'] = isset($_REQUEST['endTime']) ? $_REQUEST['endTime'] : '';
        $_POST['itemsPerPage'] = 'all';
        // 调用基础服务接口
        $return = $this->format_query('/consult/lists', $_POST);
        $list = $return['list'];
        if(empty($list)) {
            die('请选择要导出的咨询单！');
        }
       // var_dump($order_list);
        $xls_list = [];
        $sheet_titles = ['咨询单导出记录'];
        $consult_arr = [];
        $consult_arr[] = array('咨询单号', '处理状态', '填写时间', '受理人',  '咨询类型', '姓名', '电话', 'QQ', '微信', '了解渠道', '企业名', '企业地址', '咨询内容', '处理结果');

        foreach ($list as $item) {
            $consult_arr[] = array($item['id'], $item['status_name'], $item['created_time'], $item['creator'],  $item['ctype_name'], $item['name'], ' ' . $item['mobile'], ' ' . $item['qq'], $item['wechat'], $item['channel_name'], $item['company_name'], $item['company_address'], $item['content'], $item['solution']);
        }
        $xls_list[] = $consult_arr;
        $this->excel_export->export($xls_list, $sheet_titles, '咨询单导出记录.xlsx');
    }

}

/* End of file consult.php */
/* Location: :./application/controllers/consult.php */
