<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 投诉单操作
 * @author yugang@dachuwang.com
 * @version 1.0.0
 * @since 2015-05-14
 */
class Complaint extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MComplaint',
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
     * @since 2015-05-14
     */
    public function list_options() {
        // 权限校验
        $this->check_validation('complaint', 'list', '', FALSE);
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
        $ctype = C('complaint.ctype');
        $return['ctypes'] = array_values($ctype);
        $cstatus = C('complaint.status');
        $return['statuses'] = $cstatus;
        $operators = $this->MUser->get_lists('id, name', ['role_id' => C('user.admingroup.operator.type'), 'status' => C('status.common.normal')]);
        $return['operators'] = $operators;
        $this->_return_json($return);
    }

    /**
     * 投诉单列表
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function lists() {
        // 权限校验
        $this->check_validation('complaint', 'list', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/complaint/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 正常订单列表
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function list_order() {
        // 查出有效的用户
        $this->check_validation('complaint', 'create', '', FALSE);
        // $_POST['status'] = array(C('order.status.delivering.code'), C('order.status.wait_comment.code'), C('order.status.sales_return.code'), C('order.status.success.code'), C('order.status.closed.code'));
        // 调用基础服务接口
        $return = $this->format_query('/suborder/lists', $_POST);
        $this->_return_json($return);
    }

    /**
     * 查看投诉单
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function view() {

    }

    /**
     * 查看订单详情
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function order_info() {
        // 查出有效的用户
        $this->check_validation('complaint', 'create', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/order/info', $_POST);
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
            $return['ctypes'] = array_values(C('complaint.ctype'));
        }

        $this->_return_json($return);

    }

    /**
     * 添加投诉单输入页面
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function create_input() {
        // 权限校验
        $this->check_validation('complaint', 'create', '', FALSE);
        $cur = $this->userauth->current(FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/suborder/info', $_POST);
        $order_info  =  $this->format_query('/order/info', json_encode(array('order_id'=>$return ['info']['order_id'])) );
        $suborder_id =  $return['info']['id'];
        $order_id = $order_info['info']['id'];

        if($return['info']){
            $return['suborder_number'] =  $return['info']['order_number'];
            $return['order_number'] =  $order_info['info']['order_number'];

            $return['suborder_id']  =  $suborder_id;
            $return['order_id']  = $order_id;

            $return['ctypes'] = array_values(C('complaint.ctype'));
            $return['statuses'] = array_values(C('complaint.status'));
            $return['feedbacks'] = array_values(C('complaint.feedback'));
            $return['sources'] = array_values(C('complaint.source'));
            $return['relation_content'] = array_values(C('complaint.relation_content'));
            $return['deal_result'] =   array_values(C('complaint.result'));
            $return['info']['cur_name'] = $cur['name'];

            $sale_array = array(C('user.saleuser.BD.type'), C('user.saleuser.BDM.type'), C('user.saleuser.AM.type'), C('user.saleuser.SAM.type'), C('user.saleuser.CM.type'), C('user.admingroup.logistics.type'));
            $logistics_array = array(C('user.admingroup.logistics.type'));
            $sale_list = $this->MUser->get_lists('id, name', array('in' => array('role_id' => $sale_array), 'status' => C('status.common.success')));
            $logistics_list = $this->MUser->get_lists('id, name', array('in' => array('role_id' => $logistics_array), 'status' => C('status.common.success')));
            $return['sales'] = $sale_list;
            $return['logistics'] = $logistics_list;
        }
        $this->_return_json($return);
    }

    /**
     * 添加投诉单
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function create() {
        // 权限校验
        $this->check_validation('complaint', 'create', '', FALSE);
        // 表单校验
        $this->form_validation->set_rules('orderNumber', '订单编号', 'trim|required|numeric');
        $this->validate_form();
        $cur = $this->userauth->current(FALSE);

        $role_id =  $cur['role_id'];
//         if  ($role_id ==  C('user.admingroup.operator.type')) {
//             unset($_POST['progress2']);
//             unset($_POST['progress3']);
//         }else if ($role_id  == C('user.admingroup.logistics.type')){
//             unset($_POST['progress1']);
//             unset($_POST['progress2']);
//         }else if ($role_id == C('user.admingroup')){
//             unset($_POST['progress1']);
//             unset($_POST['progress3']);
//         }

        // 数据处理
        $_POST['creator_id'] = $cur['id'];
        $_POST['creator'] = $cur['name'];

        // 调用基础服务接口
        $return = $this->format_query('/complaint/create', $_POST);
        $this->_return_json($return);
    }

    /**
     * 编辑投诉单输入页面
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function edit_input() {
        // 权限校验
        $this->check_validation('complaint', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/complaint/edit_input', $_POST);

        // 调用基础服务接口
        $order_return = $this->format_query('/suborder/info',json_encode(array('order_number'=>$return['info']['order_number'])));
        $order_info  =  $this->format_query('/order/info', json_encode(array('order_id'=>$order_return['info']['order_id'])));
        $suborder_id =  $order_return['info']['id'];
        $order_id = $order_info['info']['id'];


        if($order_return['info']){
            $return['info']['suborder_number'] =  $order_return['info']['order_number'];
            $return['info']['order_number'] =  $order_info['info']['order_number'];
            $return['info']['suborder_id']  = $suborder_id;
            $return ['info']['order_id'] = $order_id;
            $return['order'] = $order_return['info'];
            $return['ctypes'] = array_values(C('complaint.ctype'));
            $return['statuses'] = array_values(C('complaint.status'));
            $return['feedbacks'] = array_values(C('complaint.feedback'));
            $return['sources'] = array_values(C('complaint.source'));
            $return['relation_content'] = array_values(C('complaint.relation_content'));
            $return['deal_result'] =   array_values(C('complaint.result'));

            $sale_array = array(C('user.saleuser.BD.type'), C('user.saleuser.BDM.type'), C('user.saleuser.AM.type'), C('user.saleuser.SAM.type'), C('user.saleuser.CM.type'), C('user.admingroup.logistics.type'));
            $logistics_array = array(C('user.admingroup.logistics.type'));
            $sale_list = $this->MUser->get_lists('id, name', array('in' => array('role_id' => $sale_array), 'status' => C('status.common.success')));
            $logistics_list = $this->MUser->get_lists('id, name', array('in' => array('role_id' => $logistics_array), 'status' => C('status.common.success')));
            $return['sales'] = $sale_list;
            $return['logistics'] = $logistics_list;
        }
        $this->_return_json($return);
    }

    /**
     * 编辑投诉单
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function edit() {
        // 权限校验
        $this->check_validation('complaint', 'edit', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/complaint/edit', $_POST);
        $this->_return_json($return);
    }

    /**
     * 删除投诉单
     * @author yugang@dachuwang.com
     * @since 2015-05-14
     */
    public function delete() {
        // 权限校验
        $this->check_validation('complaint', 'delete', '', FALSE);
        // 调用基础服务接口
        $return = $this->format_query('/complaint/delete', $_POST);
        $this->_return_json($return);
    }

    /**
     * 导出投诉单
     * @author yugang@dachuwang.com
     * @since 2015-05-12
     */
    public function export() {
        // 权限校验
        $this->check_validation('complaint', 'list', '', FALSE);
        $_POST['ids'] = isset($_REQUEST['ids']) ? $_REQUEST['ids'] : '';
        $_POST['searchValue'] = isset($_REQUEST['searchValue']) ? $_REQUEST['searchValue'] : '';
        $_POST['status'] = isset($_REQUEST['status']) ? $_REQUEST['status'] : '';
        $_POST['lineId'] = isset($_REQUEST['lineId']) ? $_REQUEST['lineId'] : '';
        $_POST['cityId'] = isset($_REQUEST['cityId']) ? $_REQUEST['cityId'] : '';
        $_POST['operator'] = isset($_REQUEST['operator']) ? $_REQUEST['operator'] : '';
        $_POST['ctype'] = isset($_REQUEST['ctype']) ? $_REQUEST['ctype'] : '';
        $_POST['startTime'] = isset($_REQUEST['startTime']) ? $_REQUEST['startTime'] : '';
        $_POST['endTime'] = isset($_REQUEST['endTime']) ? $_REQUEST['endTime'] : '';
        $_POST['itemsPerPage'] = 'all';
        // 调用基础服务接口
        $return = $this->format_query('/complaint/lists', $_POST);
        $list = $return['list'];
        if(empty($list)) {
            die('请选择要导出的投诉单！');
        }
        // var_dump($list);die;
        $xls_list = [];
        $sheet_titles = ['投诉单导出记录'];
        $complaint_arr = [];
        $complaint_arr[] = array('处理状态', '填写日期', '运营组受理人', '地区', '线路', '子订单号', '所属销售', '店铺名称', '客户姓名', '客户电话', '客户地址', '送货日期', '投诉来源', '反馈人', '投诉类型', '问题描述', '相关内容', 'sku详情', '总计金额', '客服处理意见', '销售/品控处理意见', '物流处理意见', '处理结果', '结果描述');

        foreach ($list as $item) {
            $contents = '';
            foreach ($item['contents'] as $content) {
                $contents .= $content['name'] . ' ' . $content['single_price'] . 'X' . $content['quantity'] . '=' . $content['sum_price'] . ';';
            }
            $complaint_arr[] = array($item['status_name'], $item['created_time'], $item['creator'], $item['city_name'], $item['line_name'], $item['order_number'], $item['invite_name'], $item['shop_name'], $item['name'], $item['mobile'], $item['address'], $item['deliver_date'], $item['source_cn'], $item['feedback_name'], $item['ctype_name'], $item['description'], $item['relation_content_cn'], $contents, $item['total_price'], $item['progress1'], $item['progress2'], $item['progress3'], $item['deal_result_cn'], $item['solution']);

        }
        $xls_list[] = $complaint_arr;
        $this->excel_export->export($xls_list, $sheet_titles, '投诉单导出记录.xlsx');
    }

}

/* End of file complaint.php */
/* Location: :./application/controllers/complaint.php */
