<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 *  退货单管理
 *
 * @author : maqiang@dachuwang.com
 * @version : 1.0.0
 * @since : 2015-07-20
 */
class Rejected extends MY_Controller
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
        $this->load->library(
            array(
                'form_validation',
                'excel_export'
            )
        );
    }
    
    /**
     * 获取查询条件
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function list_condition()
    {
        $conditions = $this->format_query('/rejected/get_condition');
        $this->_return_json($conditions);
    }
    
    /**
     * 获取列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function lists()
    {
        $rejected_lists_response = $this->format_query('/rejected/lists', $_POST);
        $this->_return_json($rejected_lists_response);
    }
    
    /**
     * 退货单详情
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function view()
    {
        $this->form_validation->set_rules('rejected_id', '退货退款单id', 'required|integer');
        $this->validate_form();
        $response = $this->format_query('/rejected/view', $_POST);
        $this->validate_form();
        $user = self::$user_info;
        $response['user_info'] = ['role_id'=> $user['role_id']];
        $this->_return_json($response);
    }

    /**
     * 创建退货单
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function create()
    {
        $this->form_validation->set_rules('suborder_id', '子订单id', 'required|integer');
        $this->form_validation->set_rules('reason', '退货原因', 'required|integer');
        $this->form_validation->set_rules('content', '退货内容', 'required');
        $this->form_validation->set_rules('deal_method', '退货处理方式', 'required|integer');
        $this->validate_form();
        $user = self::$user_info;
        $superadmin_type = C('user.superadmin.admin.type');
        $operator_type = C('user.admingroup.operator.type');
        if (intval($user['role_id']) != intval($operator_type)  && intval($user['role_id']) != intval($superadmin_type) ) {
            $this->_return_json(
                array(
                    'status' => C('status.auth.forbidden'),
                    'msg'    => '没有权限执行该操作',
                )
            );
       }
       $deal_method  = $_POST['deal_method'];
       
       $_POST['operator_id'] = $user['id'];
       $_POST['operator_name'] = $user['name'];
       $_POST['role_id'] = $user['role_id'];
       $_POST['role_name'] = $this->_get_role_type_cn($operator_type);
      
       $rejected_create_response =  $this->format_query('/rejected/create', $_POST);
       $this->_return_json($rejected_create_response);
    }
    
    /**
     * 创建退货单页面
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function for_create(){
        $this->form_validation->set_rules('suborder_id', '子订单id', 'required|integer');
        $this->validate_form();
        $user = self::$user_info;
        $superadmin_type = C('user.superadmin.admin.type');
        $operator_type = C('user.admingroup.operator.type');
        if (intval($user['role_id']) != intval($operator_type)  && intval($user['role_id']) != intval($superadmin_type) ) {
        $this->_return_json(
                array(
                    'status' => C('status.auth.forbidden'),
                    'msg'    => '没有权限执行该操作',
                )
            );
        }
        $response =  $this->format_query('/rejected/for_create', $_POST);
        if (!empty($response['rejected_info'])) {
            $response['rejected_info']['operator_id'] = $user['id'];
            $response['rejected_info']['operator_name'] = $user['name'];
        }
        $this->_return_json($response);
    }
    
    /**
     * 更新退货单
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function update(){
        
        $this->form_validation->set_rules('rejected_id', '退货退款单id', 'required|integer');
        $this->validate_form();
        $user = self::$user_info;
        $superadmin_type = C('user.superadmin.admin.type');
        $operator_type = C('user.admingroup.operator.type');
        if (intval($user['role_id']) != intval($operator_type)  && intval($user['role_id']) != intval($superadmin_type) ) {
            unset($_POST['suggestion']);
        }
        
        $response =  $this->format_query('/rejected/update', $_POST);
        if(isset($_POST['evidence']) && !empty($_POST['evidence'])){
            $this->_upload_evidence();
        }
        $this->_return_json($response);
    }
    /**
     * 运营人员修改状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function operator_change_status(){
        $this->form_validation->set_rules('rejected_id', '退货退款单id', 'required|integer');
        $this->validate_form();
        $user = self::$user_info;
        $superadmin_type = C('user.superadmin.admin.type');
        $operator_type = C('user.admingroup.operator.type');
        if (intval($user['role_id']) != intval($operator_type)  && intval($user['role_id']) != intval($superadmin_type) ) {
        $this->_return_json(
                array(
                    'status' => C('status.auth.forbidden'),
                    'msg'    => '没有权限执行该操作',
                )
            );
        }
        $_POST['operator_id'] = $user['id'];
        $_POST['operator_name'] = $user['name'];
        $_POST['role_id'] = $user['role_id'];
        $_POST['role_name'] = $this->_get_role_type_cn($user['role_id']);
        $response =  $this->format_query('/rejected/operator_change_status', $_POST);
        $this->_return_json($response);
    }

    /**
     * 物流人员修改状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function logistics_change_status(){
        $this->form_validation->set_rules('rejected_id', '退货退款单id', 'required|integer');
        $this->validate_form();
        $user = self::$user_info;
        $superadmin_type = C('user.superadmin.admin.type');
        $logistics_type = C('user.admingroup.logistics.type');
        if (intval($user['role_id']) != intval($logistics_type)  && intval($user['role_id']) != intval($superadmin_type) ) {
        $this->_return_json(
                array(
                    'status' => C('status.auth.forbidden'),
                    'msg'    => '没有权限执行该操作',
                )
            );
        }
        //$this->form_validation->set_rules('suborder_id', '退货原因', 'required');
        
        $_POST['operator_id'] = $user['id'];
        $_POST['operator_name'] = $user['name'];
        $_POST['role_id'] = $user['role_id'];
        $_POST['role_name'] = $this->_get_role_type_cn($user['role_id']);
        $response =  $this->format_query('/rejected/logistics_change_status', $_POST);
        $this->_return_json($response);
    }
    
    /**
     * 财务人员修改状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function finance_change_status(){
        $this->form_validation->set_rules('rejected_id', '退货退款单id', 'required|integer');
        $this->validate_form();
        $user = self::$user_info;
        $superadmin_type = C('user.superadmin.admin.type');
        $finance_type = C('user.admingroup.finance.type');
        if (intval($user['role_id']) != intval($finance_type)  && intval($user['role_id']) != intval($superadmin_type) ) {
        $this->_return_json(
                array(
                    'status' => C('status.auth.forbidden'),
                    'msg'    => '没有权限执行该操作',
                )
            );
        }
        $this->form_validation->set_rules('suborder_id', '退货原因', 'required');
        $_POST['operator_id'] = $user['id'];
        $_POST['operator_name'] = $user['name'];
        $_POST['role_id'] = $user['role_id'];
        $_POST['role_name'] = $this->_get_role_type_cn($user['role_id']);
        $response =  $this->format_query('/rejected/finance_change_status', $_POST);
        $this->_return_json($response);
    }

    
    /**
     * 日志列表
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function log_list(){
        $this->form_validation->set_rules('rejected_id', '退货退款单id', 'required|integer');
        $this->validate_form();
        $response =  $this->format_query('/rejected/log_list', $_POST);
        $this->_return_json($response);
    }
    
    /**
     * 增加备注
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function add_remark(){
        $this->form_validation->set_rules('rejected_id', '退货退款单id', 'required|integer');
        $this->form_validation->set_rules('content', '备注内容', 'required');
        $this->validate_form();
        $user = self::$user_info;
        $_POST['operator_id'] = $user['id'];
        $_POST['operator_name'] = $user['name'];
        $_POST['role_id'] = $user['role_id'];
        $_POST['role_name'] = $this->_get_role_type_cn($user['role_id']);
        $response =  $this->format_query('/rejected/add_remark', $_POST);
        $this->_return_json($response);
    }
    
    /**
     * 关闭退货单状态
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function close_rejected(){
        $this->form_validation->set_rules('rejected_id', '退货退款单id', 'required|integer');
        $this->form_validation->set_rules('content', '内容', 'required');
        $this->validate_form();
        $user = self::$user_info;
        $_POST['operator_id'] = $user['id'];
        $_POST['operator_name'] = $user['name'];
        $_POST['role_id'] = $user['role_id'];
        $_POST['role_name'] = $this->_get_role_type_cn($user['role_id']);
        $response =  $this->format_query('/rejected/close_rejected', $_POST);
        $this->_return_json($response);
    }
    
    
    /**
     * 上传凭证
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    private function _upload_evidence()
    {
        $response = $this->format_query('/rejected/upload_evidence', $this->post);
        $this->_return_json($response);
    }
    
    /**
     * 根据订单编号获取订单
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function get_order_by_num(){
        $this->form_validation->set_rules('sku_number', '订单编号', 'required|numeric');
        $this->form_validation->set_rules('is_parent', 'is_parent', 'required|regex_match[/[01]/]');
        $this->validate_form();
        $sku_number = $_POST['sku_number'];
        $is_parent = $_POST['is_parent'];
        if ($is_parent == 1) {
            $response = $this->format_query('/order/info', array("order_number"=>$sku_number));
        }else{
            $response = $this->format_query('/suborder/info', array("order_number"=>$sku_number));
        }
        $response['status'] = C('status.req.invalid');
        $this->_return_json($response);
    }

    /**
     * 根据订单id获取订单
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function get_order_by_id(){
        $this->form_validation->set_rules('suborder_id', '子订单id', 'required|integer');
        $this->validate_form();
        $suborder_id = $_POST['suborder_id'];
        $response = $this->format_query('/suborder/info', array("suborder_id"=>$suborder_id));
        $this->_return_json($response);
    }
    
    /**
     * 获取已完成和已签收子订单列表 
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function get_suborder_list(){
        $_POST['status'] = array(1,6);
        $response = $this->format_query('/suborder/lists', $_POST);
        $this->_return_json($response);
    }
    
    /**
     * 导出
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    public function export() {
        if (!isset($_GET['ids']) || empty($_GET['ids'])){
            $this->_return_json(
                array(
                    'status'  => C('status.req.invalid'),
                    'msg'     => '请填写完整必填的信息',
                )
            );
        }
        $id_arr = explode(',', $_GET['ids']);
        $id_arr = array_filter($id_arr);
        $_POST['ids'] = $id_arr;
        // 调用基础服务接口
        $return = $this->format_query('/rejected/export', $_POST);
        if ($return['status'] == 0){
            $rejected_list = $return['list'];
            $export_info = $this->_export($rejected_list);
            $this->excel_export->export($export_info['list'], $export_info['titles'], '退货退款单导出记录.xlsx');
        }
    }
    
    /**
     * 检查是否是数组
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     * @deprecated
     */
    public function _check_is_array($arr){
        if (is_array($arr)) {
            foreach ($arr as $k=>$val){
                if (!is_numeric($val)){
                   unset($arr[$k]); 
                }
            }
            if(count($arr)>0){
                return true;
            }
        }
        return false;
    }
   
    /**
     * 导出工具类
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    private function _export($rejected_list){
        $xls_list = [];
        $sheet_titles = [];
        foreach ($rejected_list as $rejected) {
            $rejected_arr = [];
            $rejected_arr[] = array('', '', '', '',  '退货退款单');
            $rejected_arr[] = array('');
            $rejected_arr[] = array('填写日期：', $rejected['created_time']);
            $rejected_arr[] = array('');
            $rejected_arr[] = array('路线：', $rejected['line_cn'], '', '', '店铺名称：', $rejected['shop_name']);
            $rejected_arr[] = array('');
            $rejected_arr[] = array('客户姓名：', ' ' . $rejected['name'], '', '', '客户电话：', $rejected['mobile']);
            $rejected_arr[] = array('');
            $rejected_arr[] = array('收货人姓名：', ' ' . $rejected['recieve_name'], '', '', '收货人电话：', $rejected['recieve_mobile']);
            $rejected_arr[] = array('');
//             $address_arr = $this->_split_str($order['address']);
//             foreach ($address_arr as $k => $v) {
//                 $rejected_arr[] = array($k == 0 ? '送货地点：' : '', $v, '', '', '', '');
//             }
            $rejected_arr[] = array('送货地点:', $rejected['address']);
            $rejected_arr[] = array('');
            $rejected_arr[] = array('母订单订单号：' . $rejected['order_number']);
            $rejected_arr[] = array('');
            $rejected_arr[] = array('子订单订单号：' . $rejected['suborder_number']);
            $rejected_arr[] = array('');
            foreach ($rejected['content'] as $k => $v) {
                $rejected_arr[] = array($k == 0 ? '退货内容：' : '', $v['name'], '','' ,'数量：'.$v['quantity']);
            }
            $rejected_arr[] = array('');
            $rejected_arr[] = array('原因 :', $rejected['reason_cn']);
            $rejected_arr[] = array('');
            $suggestion_arr = $this->_split_str($rejected['suggestion']);
            foreach ($suggestion_arr as $k => $v) {
                $rejected_arr[] = array($k == 0 ? '处理意见：' : '', $v);
            }
            $rejected_arr[] = array('');
            $rejected_arr[] = array('');
            $rejected_arr[] = array('库房：', '', '', '', '客户：');
            $rejected_arr[] = array('');
            $rejected_arr[] = array('配送司机：', '', '', '', '日期:');
        
            $xls_list[] = $rejected_arr;
            $sheet_titles[] = $rejected['id'];
        }
        return array('list'=> $xls_list, 'titles'=>$sheet_titles);
    }
    
    /**
     * 获取角色中文
     *
     * @author maqiang@dachuwang.com
     * @since 2015-08-11
     */
    private function _get_role_type_cn($role_id){
        $role_types = C('user.admingroup');
        foreach($role_types as  $role_type){
            if (intval($role_type['type'] == intval($role_id))){
               return $role_type['name'];
            }
        }

        $admin_role_types = C('user.superadmin.admin');
        if (intval($admin_role_types['type']) == $role_id ){
            return  $admin_role_types['name'];
        }
        return "未知";
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

/* End of file billing.php */
/* Location: ./application/controllers/billing.php */
