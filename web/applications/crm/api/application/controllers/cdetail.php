<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @author liudeen@dachuwang.com
 * @description CRM客户动态API
 * @time 2015-05-19
 */
class Cdetail extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }
    private $_check_lists = ['action'];
    private $_action_lists = ['get_all','get_customer_orders','get_order_detail','get_history_belong_detail', 'get_buy_analysis'];

    //检测必须参数是否传递和是否正确
    private function _checkNecessary() {
        foreach($this->_check_lists as $key) {
            if(empty($_POST[$key])) {
                return FALSE;
            }
        }
        return TRUE;
    }

    private function _check_action_valid() {
        if(!in_array($_POST['action'], $this->_action_lists)) {
            return FALSE;
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

    /*
     * @param uid(integer)
     * @description 获取客户的最新短信
     * @return string 短信内容
     */
    private function _get_latest_sms($uid) {
        $postData = ['uid'=>$uid,'action'=>'latest_sms'];
        $result = $this->format_query('/cdetail/index', $postData);
        if(!$result) {
            throw new Exception('获取最新短信失败');
        } else {
            return $result['res'];
        }
    }

    /*
     * @param uid(integer)
     * @param arr(array) 要查询的项目
     * @description 获取客户历史数据
     * @return array
     */
    private function _get_history_data($uid, array $arr) {
        $postData = ['uid'=>$uid, 'target'=>$arr,'action'=>'history_data'];
        $result = $this->format_query('/cdetail/index', $postData);
        if(!$result) {
            throw new Exception('获取客户历史数据失败');
        } else {
            return $result['res'];
        }
    }

    /*
     * @param uid(integer)
     * @description 查询客户的基本信息
     * @return array
     */
    private function _get_customer_info($uid) {
        $postData = ['uid'=>$uid,'action'=>'customer_info'];
        $result = $this->format_query('/cdetail/index', $postData);
        if(!$result) {
            throw new Exception('查询客户信息失败');
        } else {
            return $result['res'];
        }
    }

    private function _get_history_belong_detail() {
        $uid = isset($_POST['uid']) ? $_POST['uid'] : NULL;
        if(!$uid) {
            throw new Exception('lack of uid');
        }
        $postData = ['uid'=>$uid,'action'=>'history_belong_detail'];
        $result = $this->format_query('/cdetail/index', $postData);
        if($result['status']!=C('status.req.success')) {
            throw new Exception('查询客户历史归属失败');
        } else {
            return $result['res'];
        }
    }

    private function _get_buy_analysis() {
        $uid = intval($_POST['uid']);
        $postData = ['customer_ids' => [$uid]];
        if(isset($_POST['start_time'])) {
            $postData['start_time'] = $_POST['start_time'];
        }
        if(isset($_POST['end_time'])) {
            $postData['end_time'] = $_POST['end_time'];
        }
        $buy_data = $this->format_query('/customer_buyaction_analysis/get_customer_buy_action_analysis', $postData);
        $buy_consist = $this->format_query('/customer_buyaction_analysis/get_first_category_by_customer_ids',$postData);
        if(!isset($buy_data['list']) || !isset($buy_consist['list']) || !is_array($buy_data['list'][$uid]) || !is_array($buy_consist['list'][$uid])) {
            return [];
        }
        $result = [];
        $arr = [];
        foreach($buy_data['list'][$uid] as $key => $val) {
            $arr[] = ['name' => $key, 'value' => $val];
        }
        $result['buy_data'] = $arr;
        $arr = [];
        foreach($buy_consist['list'][$uid] as $key => $val) {
            $arr[] = ['name' => $key, 'value' => $val];
        }
        $result['buy_consist'] = $arr;
        return $result;
    }

    //获取客户详情页面的所有数据，包括最新短信，历史数据，客户信息
    private function _get_all() {
        $uid = intval($_POST['uid']);
        //订单数 订单金额 历史所属销售数
        $his_target = ['order_num','order_amount'];
        $arr = [
            'sms' => $this->_get_latest_sms($uid),
            'his_data' => $this->_get_history_data($uid, $his_target),
            'basic_info' => $this->_get_customer_info($uid)
        ];
        return $arr;
    }

    private function _get_time_line() {
        $bgtm = isset($_POST['begin_time']) ? $_POST['begin_time'] : NULL;
        $edtm = isset($_POST['end_time']) ? $_POST['end_time'] : NULL;
        if(!$bgtm && !$edtm) {
            return FALSE;
        }
        $arr = [];
        if($bgtm !== NULL) {
            $arr['begin_time'] = $bgtm;
        }
        if($edtm !== NULL) {
            $arr['end_time'] = $edtm;
        }
        return $arr;
    }

    private function _get_customer_orders() {
        $uid = intval($_POST['uid']);
        $postData = [
            'uid' => $uid,
            'action'=>'customer_orders',
            'itemsPerPage' => isset($_POST['itemsPerPage']) ? $_POST['itemsPerPage'] : 10,
            'currentPage' => isset($_POST['currentPage']) ? $_POST['currentPage'] : 1
        ];
        $time_arr = $this->_get_time_line();
        if($time_arr !== FALSE) {
            $postData['time_arr'] = $time_arr;
        }
        try {
            $result = $this->format_query('cdetail/index', $postData);
            if($result['status'] !== C('status.req.success')) {
                throw new Exception($result['msg']);
            }
            return $result['res'];
        } catch (Exception $e) {
           throw new Exception($e->getMessage());
        }
    }

    //根据订单号获取订单详情
    private function _get_order_detail() {
        $order_number = $_POST['order_number'];
        $postData = ['order_number'=>$order_number,'action'=>'order_detail'];
        try {
            $result = $this->format_query('cdetail/index', $postData);
            if(intval($result['status']) !== 0) {
                throw new Exception($result['msg']);
            }
            return $result['res'];
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function _get_sub_accounts() {
        if(empty($_POST['id'])) {
            return [];
        }
        $postData = [
            'id' => intval($_POST['id']),
            'action' => 'get_sub_accounts'
        ];
        $result = $this->format_query('cdetail/index', $postData);
        if($result && $result['status'] == 0) {
            return $result['res'];
        }
        return [];
    }

    //请求入口
    public function index() {
        //是否登陆
        /*$cur = $this->_check_login();
        if(!$cur) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), 'no access'));
        }*/
        //请求是否合法
        if(($reason=$this->_checkNecessary()) !== TRUE) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $reason));
        }
        $action = $_POST['action'];
        $action = '_'.$action;
        try {
            $this->_return_json($this->_assemble_res($this->$action(), C('status.req.success'), 'request success'));
        }catch(Exception $e) {
            $this->_return_json($this->_assemble_err(C('status.req.failed'), $e->getMessage()));
        }
    }
}
/* End of file user.php */
/* Location: :./application/controllers/user.php */
