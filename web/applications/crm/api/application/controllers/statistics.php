<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @author liudeen@dachuwang.com
 * @description CRM统计API
 * @time 2015-03-27
 */
class Statistics extends MY_Controller {
    //允许用户请求的方法
    private $_action_list = array('get_statistics', 'get_total_statistics','get_query','get_new_add_orders','get_back_flow_water');
    private $_role_id_list = array(12,13,14,15,16);
    private $_role_id=NULL;
    //需要统计的数据
    private $_statistic_list = array(
        12 => array(
            'potential_customer_num' => '新增潜在客户数',
            'customer_num' => '新增注册客户数',
            'order_num' => '新增下单数',
            'flow_water' => '新增回款流水',
            'order_customer_num' => '已下单客户数',
            'not_order_customer_num_care_time' => '未下单客户数'
        ),
        13 => array(
            'potential_customer_num' => '新增潜在客户数',
            'customer_num' => '新增注册客户数',
            'order_num' => '新增下单数',
            'flow_water' => '新增回款流水',
            'order_customer_num' => '已下单客户数',
            'not_order_customer_num_care_time' => '未下单客户数'
        ),
        16 => array(
            'potential_customer_num' => '新增潜在客户数',
            'customer_num' => '新增注册客户数',
            'order_num' => '新增下单数',
            'flow_water' => '新增回款流水',
            'order_customer_num' => '已下单客户数',
            'not_order_customer_num_care_time' => '未下单客户数'
        )
    );
    private $_all_list = array(
        12 => array(
            'first_order_num' => '首单数',
            'first_finish_order_num' => '已完成首单数'
        ),
        13 => array(
            'first_order_num' => '首单数',
            'first_finish_order_num' => '已完成首单数'
        ),
        14 => array(
            'customer_num' => '客户数',
            'finish_order_num' => '完成订单数'
        ),
        15 => array(
            'customer_num' => '客户数',
            'finish_order_num' => '完成订单数'
        )
    );
    //允许按时间查询的种类
    private $_time_type = array('by_day','by_week', 'by_month', 'all', 'optional');
    public function __construct() {
        parent::__construct();
    }

    //检测必须参数是否传递和是否正确
    private function _check_necessary() {
        if(!isset($_POST['action'])) {
            return 'no action';
        } else if(!in_array($_POST['action'], $this->_action_list)) {
            return 'invalid action';
        }
        if(!isset($_POST['role_id'])) {
            return 'no roleid';
        } else if(!in_array(intval($_POST['role_id']), $this->_role_id_list)) {
            return 'invalid roleid';
        }
        if($_POST['action'] === 'get_query') {
            if(!isset($_POST['begin_time']) || !isset($_POST['end_time'])) {
                return 'no time';
            }
        }
        return TRUE;
    }

    //封装结果输出
    private function _assemble_res($arr, $status=0, $msg='') {
        return array(
            'status' => $status,
            'msg' => $msg,
            'list' => $arr
        );
    }

    //封装错误输出
    private function _assemble_err($status=-1, $msg='error') {
        return array(
            'status' => $status,
            'msg' => $msg
        );
    }

    //检查是否登录，若登录返回用户信息
    private function _check_login() {
        $cur = $this->userauth->current(FALSE);
        if(!$cur) {
            return FALSE;
        }
        return $cur;
    }

    //获取$user_id的下属，返回一个下属id的数组
    private function _get_subordinates($user_id) {
        // 获取当前BD下所有BD列表
        $res = $this->format_query('/customer/list_group', array('user_id' => $user_id));
        $bd_id = array();
        $bd_id[] = $user_id;
        foreach ($res['list']['list'] as $v) {
            $bd_id[] = $v['uid'];
        }
        return $bd_id;
    }

    //封装POST数据并调用service接口，返回统计数量
    //前端发送过来的begin_time是unix时间戳
    private function _send_query($action, $bd_id, $time_type) {
        $postdata = array(
            'time_type' => $time_type,
            'bd_ids' => is_array($bd_id) ? $bd_id : array($bd_id),
            'role_id' => $this->_role_id
        );
        if($time_type === 'optional') {
            $postdata['begin_time'] = $_POST['begin_time'];
            $postdata['end_time'] = $_POST['end_time'];
        }
        $res = $this->format_query('/performance/'.$action, $postdata);
        if($res['status'] == 0) {
            return $res['res'];
        } else {
            return $res['msg'];
        }
    }

    //检查查询时间类型是否合法，默认返回查询今日
    private function _check_time_type($time_type) {
        if(in_array($time_type, $this->_time_type)) {
            return $time_type;
        }
        return 'by_day';
    }

    //判断是否是bdm或者sam
    private function _is_manager() {
        if(!isset($_POST['bd_id'])) {
            return FALSE;
        }
        $cur = $this->_check_login();
        if($cur) {
            return FALSE;
        }
        if($cur['id'] !== $_POST['bd_id']) {
            return FALSE;
        }
        return TRUE;
    }

    //用户自定义区间查询
    private function _get_query() {
        $cur = $this->_check_login();
        $bd_id = isset($_POST['bd_id']) ? $_POST['bd_id'] : $cur['id'];
        //如果是BDM或者SAM，查询自己相当于查询总计
        if($this->_is_manager()) {
           $bd_id = $this->_get_subordinates($bd_id); 
        }
        return $this->_get_detail_statistics($bd_id, 'optional');
    }

    //循环统计每个需要统计的项目
    private function _get_detail_statistics($bd_id, $time_type='by_day') {
        $result = array();
        $role_id = intval($_POST['role_id']);
        foreach($this->_statistic_list[$role_id] as $key => $value) {
            $result[] = array(
                'key' => $value,
                'val' => $this->_send_query($key, $bd_id, $time_type)
            );
        }
        return $result;
    }

    private function _get_all($bd_id, $time_type) {
        $result = array();
        $role_id = intval($_POST['role_id']);
        foreach($this->_all_list[$role_id] as $key => $value) {
            $result[] = array(
                'key' => $value,
                'val' => $this->_send_query($key, $bd_id, $time_type)
            );
        }
        return $result;
    }

    private function _get_capability($bd_id) {
        $result = $this->format_query('/performance/get_capability', ['bd_id' => $bd_id]);
        if(!$result || $result['status']!=C('status.req.success')) {
            return '未知';
        }
        return $result['res'];
    }

    //获取当前BD统计信息
    private function _get_statistics() {
        $cur = $this->_check_login();
        $bd_id = isset($_POST['bd_id']) ? $_POST['bd_id']:$cur['id'];
        $statistic =  array(
            array(
                'heading' => '今日',
                'data' => $this->_get_detail_statistics($bd_id, 'by_day')
            ),
            array(
                'heading' => '本周',
                'data' => $this->_get_detail_statistics($bd_id, 'by_week')
            ),
            array(
                'heading' => '本月',
                'data' => $this->_get_detail_statistics($bd_id, 'by_month')
            ),
            array(
                'heading' => '客容量',
                'data' => $this->_get_capability($bd_id)
            )
        );
        return $statistic;
    }

    /**
     * 统计BD经理下所有BD的业绩
     * @author yugang@dachuwang.com
     * @since 2015-03-31
     * @modified liudeen
     * @latest 2015-05-08
     */
    private function _get_total_statistics() {
        $cur = $this->_check_login();
        $user_id = $cur['id'];
        if(!empty($_POST['user_id'])){
           $user_id = $_POST['user_id'];
        }
        //获得下属的ids
        $bd_id = $this->_get_subordinates($user_id);
        $statistic =  array(
            array(
                'heading' => '今日',
                'data' => $this->_get_detail_statistics($bd_id, 'by_day')
            ),
            array(
                'heading' => '本周',
                'data' => $this->_get_detail_statistics($bd_id, 'by_week')
            ),
            array(
                'heading' => '本月',
                'data' => $this->_get_detail_statistics($bd_id, 'by_month')
            )
        );
        return $statistic;
    }

    private function _get_new_add_orders() {
        $cur = $this->_check_login();
        if(!$cur) {
            $this->_assemble_err(C('status.req.failed'), 'not login');
        }
        $bd_id = isset($_POST['bd_id']) ? $_POST['bd_id'] : $cur['id'];
        if(!isset($_POST['time_type'])) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), 'no time param'));
        }
        $postData = ['bd_ids' => [$bd_id]];
        if(isset($_POST['time_type'])) {
            $postData['time_type'] = $_POST['time_type'];
        }
        if(isset($_POST['begin_time'])) {
            $postData['begin_time'] = $_POST['begin_time'];
        }
        if(isset($_POST['end_time'])) {
            $postData['end_time'] = $_POST['end_time'];
        }
        if(isset($_POST['itemsPerPage']) && isset($_POST['currentPage'])) {
            $postData['itemsPerPage'] = $_POST['itemsPerPage'];
            $postData['currentPage'] = $_POST['currentPage'];
        }
        $return = $this->format_query('/performance/get_new_add_orders', $postData);
        $this->_return_json($this->_assemble_res($return['res'], C('status.req.success'), 'success'));
    }

    private function _get_back_flow_water() {
        $cur = $this->_check_login();
        if(!$cur) {
            $this->_assemble_err(C('status.req.failed'), 'not login');
        }
        $bd_id = $cur['id'];
        if(!isset($_POST['time_type'])) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), 'no time param'));
        }
        $postData = ['bd_ids' => [$bd_id]];
        if(isset($_POST['time_type'])) {
            $postData['time_type'] = $_POST['time_type'];
        }
        if(isset($_POST['begin_time'])) {
            $postData['begin_time'] = $_POST['begin_time'];
        }
        if(isset($_POST['end_time'])) {
            $postData['end_time'] = $_POST['end_time'];
        }
        if(isset($_POST['itemsPerPage']) && isset($_POST['currentPage'])) {
            $postData['itemsPerPage'] = $_POST['itemsPerPage'];
            $postData['currentPage'] = $_POST['currentPage'];
        }
        $return = $this->format_query('/performance/get_back_flow_water', $postData);
        $this->_return_json($this->_assemble_res($return['res'], C('status.req.success'), 'success'));
    }

    private function _get_order_customer_lists() {
        $cur = $this->_check_login();
        if(!$cur) {
            $this->_assemble_err(C('status.req.failed'), 'not login');
        }
        $bd_id = $cur['id'];
        if((!isset($_POST['begin_time']) && !isset($_POST['end_time'])) || (!isset($_POST['time_type']))) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), 'no time param'));
        }
        $postData = ['bd_ids' => [$bd_id]];
        if(isset($_POST['time_type'])) {
            $postData['time_type'] = $_POST['time_type'];
        }
        if(isset($_POST['begin_time'])) {
            $postData['begin_time'] = $_POST['begin_time'];
        }
        if(isset($_POST['end_time'])) {
            $postData['end_time'] = $_POST['end_time'];
        }
        $return = $this->format_query('/performance/get_order_customer_lists', $postData);
        $this->_return_json($this->_assemble_res($return['res'], C('status.req.success'), 'success'));
    }

    private function _get_not_order_customer_lists() {
        $cur = $this->_check_login();
        if(!$cur) {
            $this->_assemble_err(C('status.req.failed'), 'not login');
        }
        $bd_id = $cur['id'];
        if((!isset($_POST['begin_time']) && !isset($_POST['end_time'])) || (!isset($_POST['time_type']))) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), 'no time param'));
        }
        $postData = ['bd_ids' => [$bd_id]];
        if(isset($_POST['time_type'])) {
            $postData['time_type'] = $_POST['time_type'];
        }
        if(isset($_POST['begin_time'])) {
            $postData['begin_time'] = $_POST['begin_time'];
        }
        if(isset($_POST['end_time'])) {
            $postData['end_time'] = $_POST['end_time'];
        }
        $return = $this->format_query('/performance/get_not_order_customer_lists', $postData);
        $this->_return_json($this->_assemble_res($return['res'], C('status.req.success'), 'success'));
    }

    //请求入口
    public function index() {
        //是否登陆
        $cur = $this->_check_login();
        if(!$cur) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), 'no access'));
        }
        if($cur['role_id']) {
            $this->_role_id = $cur['role_id'];
        } else {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), 'can not get role_id'));
        }
        //请求是否合法
        if(($reason=$this->_check_necessary()) !== TRUE) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $reason));
        }
        $action = $_POST['action'];
        $action = '_'.$action;
        $this->_return_json($this->_assemble_res($this->$action(), C('status.req.success'), 'request success'));
    }
}
/* End of file user.php */
/* Location: :./application/controllers/user.php */
