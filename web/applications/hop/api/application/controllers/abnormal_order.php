<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 异常单操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-05-09
 */
class Abnormal_order extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MAbnormal_order',
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
     * 线路列表
     * @author yugang@dachuwang.com
     * @since 2015-05-07
     */
    public function list_options() {
        // 权限校验
        $this->check_validation('order', 'list', '', FALSE);
        // 调用基础服务接口
        // 查询所有线路
        $_POST['itemsPerPage'] = 'all';
        $return = $this->format_query('/line/lists', $_POST);
        $cities = $this->MLocation->get_lists(
            "id, name",
            array(
                'upid'   => 0,
                'status' => 1
            )
        );
        $return['cities'] = $cities;
        $site = C('site.code');
        $return['sites'] = array_values($site);
        $otype = C('abnormal_order.otype');
        $return['otypes'] = array_values($otype);
        $this->_return_json($return);
    }

    /**
     * 异常单列表
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function lists() {
        // 权限校验
        $this->check_validation('abnormal_order', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/abnormal_order/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 正常订单列表
     * @author yugang@dachuwang.com
     * @since 2015-03-07
     */
    public function list_order() {
        // 查出有效的用户
        $this->check_validation('abnormal_order', 'create', '', FALSE);
        // $_POST['status'] = array(C('order.status.delivering.code'), C('order.status.wait_comment.code'), C('order.status.sales_return.code'), C('order.status.success.code'), C('order.status.closed.code'));
        // 调用基础服务接口
        $return = $this->format_query('/suborder/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 查看异常单
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function view() {

    }

    /**
     * 查看订单详情
     * @author yugang@dachuwang.com
     * @since 2015-05-11
     */
    public function order_info() {
        // 查出有效的用户
        $this->check_validation('abnormal_order', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/suborder/info', $_POST);
        if($return['info']){
            $_POST['itemsPerPage'] = 'all';
            $line_return = $this->format_query('/line/lists', $_POST);
            $cities = $this->MLocation->get_lists(
                "id, name",
                array(
                    'upid'   => 0,
                    'status' => 1
                )
            );
            $return['lines'] = $line_return['list'];
            $return['cities'] = $cities;
            $return['sites'] = array_values(C('site.code'));
            $return['otypes'] = array_values(C('abnormal_order.otype'));
        }

        $this->_return_json($return);
    }

    /**
     * 添加异常单输入页面
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function create_input() {
        // 权限校验
        $this->check_validation('abnormal_order', 'create', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/suborder/info', $_POST);
        if($return['info']){
            $return['info']['cur_name'] = $cur['name'];
            $return['otypes'] = array_values(C('abnormal_order.otype'));
            $return['statuses'] = array_values(C('abnormal_order.status'));
        }
        $this->_return_json($return);
    }

    /**
     * 添加异常单
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function create() {
        // 权限校验
        $this->check_validation('abnormal_order', 'create', '', FALSE);
        // 表单校验
        $this->form_validation->set_rules('orderNumber', '订单编号', 'trim|required|numeric');
        $this->validate_form();
        $cur = $this->userauth->current(FALSE);

        // 数据处理
        $_POST['creator'] = $cur['name'];
        $_POST['creator_id'] = $cur['id'];

        // 调用基础服务接口
        $return = $this->format_query('/abnormal_order/create', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑异常单输入页面
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function edit_input() {
        // 权限校验
        $this->check_validation('abnormal_order', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/abnormal_order/edit_input', $_POST);
        $order_return = $this->format_query('/suborder/info', array('order_number' => $return['info']['order_number']));
        $return['otypes'] = array_values(C('abnormal_order.otype'));
        $return['statuses'] = array_values(C('abnormal_order.status'));
        $return['order'] = $order_return['info'];
        $this->_return_json($return);
    }

    /**
     * 编辑异常单
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function edit() {
        // 权限校验
        $this->check_validation('abnormal_order', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/abnormal_order/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 删除异常单
     * @author yugang@dachuwang.com
     * @since 2015-05-09
     */
    public function delete() {
        // 权限校验
        $this->check_validation('abnormal_order', 'delete', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/abnormal_order/delete', $_POST);
        $this->_return_json($return);
    }

    /**
     * 导出异常单
     * @author yugang@dachuwang.com
     * @since 2015-05-12
     */
    public function export() {
        // 权限校验
        $this->check_validation('abnormal_order', 'list', '', FALSE);
        $_POST['ids'] = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : '';
        $_POST['itemsPerPage'] = 'all';
        // 调用基础服务接口
        $return = $this->format_query('/abnormal_order/lists', $_POST);
        $order_list = $return['list'];
        if(empty($order_list)) {
            die('请选择要导出的异常单！');
        }
       // var_dump($order_list);
        $xls_list = [];
        $sheet_titles = [];
        foreach ($order_list as $order) {
            $order_arr = [];
            $order_arr[] = array('', '', '', '',  '异常单', '', '', $order['otype_name']);
            $order_arr[] = array('', '', '', '', '', '', '', '');
            $order_arr[] = array('填写日期：', $order['created_time'], '', '', '', '');
            $order_arr[] = array('', '', '', '', '', '', '', '');
            $order_arr[] = array('订单路线：', $order['line_name'], '', '', '客户名称：', $order['name']);
            $order_arr[] = array('', '', '', '', '', '', '', '');
            $order_arr[] = array('客户电话：', ' ' . $order['mobile'], '', '', '店铺名称：', $order['shop_name']);
            $order_arr[] = array('', '', '', '', '', '', '', '');
            $address_arr = $this->_split_str($order['address']);
            foreach ($address_arr as $k => $v) {
                $order_arr[] = array($k == 0 ? '送货地点：' : '', $v, '', '', '', '');
            }
            $order_arr[] = array('', '', '', '', '', '', '', '');
            $order_arr[] = array('订单号：' . $order['order_number'], '', '', '', '');
            $order_arr[] = array('', '', '', '', '', '', '', '');
            foreach ($order['contents'] as $k => $v) {
                $order_arr[] = array($k == 0 ? '订单内容：' : '', $v['name'], '', '', '', '');
            }
            $order_arr[] = array('', '', '', '', '', '', '', '');
            $reason_arr = $this->_split_str($order['reason']);
            foreach ($reason_arr as $k => $v) {
                $order_arr[] = array($k == 0 ? '原因：' : '', $v, '', '', '', '');
            }
            $order_arr[] = array('', '', '', '', '', '', '', '');
            $solution_arr = $this->_split_str($order['solution']);
            foreach ($solution_arr as $k => $v) {
                $order_arr[] = array($k == 0 ? '处理方案：' : '', $v, '', '', '', '');
            }
            $order_arr[] = array('', '', '', '', '', '', '', '');
            $order_arr[] = array('库房：', '', '', '', '', '客户：', '', '', '', '');
            $order_arr[] = array('', '', '', '', '', '', '', '');
            $order_arr[] = array('配送司机：', '', '', '', '', '日期:', '', '', '', '');
            $order_arr[] = array('', '', '', '', '', '', '', '');

            $xls_list[] = $order_arr;
            $sheet_titles[] = $order['id'];
        }
        $this->excel_export->export($xls_list, $sheet_titles, '异常单导出记录.xlsx');
    }

    private function _split_str($str, $len = 30) {
        $str_len = mb_strlen($str, 'utf-8');
        $rows = $str_len / $len;
        $str_arr = [];
        for($i=0; $i<$rows; $i++){
            $start = $len * $i;
            $length = $len;
            if($start + $length > $str_len) {
                $length = $str_len - $start;
            }
            $str_arr[] = mb_substr($str, $start, $length);
        }

        return $str_arr;
    }

}

/* End of file abnormal_order.php */
/* Location: :./application/controllers/abnormal_order.php */
