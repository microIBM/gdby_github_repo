<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed') ;
/**
 * 客户操作model
 *
 * @author : yugang@dachuwang.com
 * @version : 1.0.0
 * @since : 2015-03-04
 */
class MCustomer extends MY_Model {
    use MemAuto;

    private $table = 't_customer' ;
    protected $_salt = NULL ;

    public function __construct () {
        parent::__construct($this->table) ;
    }

    /**
     * 以函数返回值形式返回用户信息(不包含密码)
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function get_user_info ($query) {
        $user_info = $this->get_one('*', $query) ;
        return $user_info ;
    }

    /**
     * 检测手机号是否唯一
     *
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     * @return 检测结果
     */
    public function check_mobile_unique ($mobile) {
        if ($this->get_one('*', array('mobile' => $mobile, 'status !=' => C('status.common.del')))) {
            return FALSE ;
        }
        return TRUE ;
    }

    /**
     * 修改用户状态
     *
     * @author yugang@dachuwang.com
     * @since 2014-03-04
     */
    public function toggle_status ($uid, $status) {
        return $this->update_by('id', $uid, array (
                'status' => $status
        )) ;
    }

    /**
     * 重置密码 ription 重置用户密码为手机号后6位
     *
     * @author yugang@dachuwang.com
     * @since 2014-03-04
     */
    public function reset_password ($uid, $password) {
        $user = $this->get_one('*', array (
                'id' => $uid
        )) ;
        if (! $user) {
            return FALSE ;
        }
        // $password = substr($user['mobile'], 5, 6);
        // 重置为随机密码
        $new_password = $this->_parse_password($password, $user['salt']) ;
        $result = $this->update_by('id', $uid, array (
                'password' => $new_password
        )) ;
        return $result ;
    }

    public function get_customer_total ($where = array()) {
        return $this->count($where) ;
    }

    /**
     * 获取每日新增用户及每日总用户数
     *
     * @param unknown $param 查询参数：site_id,stime,etime
     * @param string $display_list 是否显示每日新增用户list
     * @return array
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_customer_lists ($param = array(), $display_list = false) {

        //区分城市 wangyang@dachuwang.com
        $city_id = isset($_POST['city_id']) ? $_POST['city_id'] : 0;
        if($city_id) {
            $where['province_id = '] = $city_id;
        }
        if($param['site_id']) {
            $where['site_id'] = $param['site_id'] ;
        }

        if (isset($param['stime']) && isset($param['etime'])) {
            $where['created_time >='] = $param['stime'] ;
            $where['created_time <='] = $param['etime'] ;
            $this->db->select('id,invite_id,is_active,site_id,created_time,status')->from($this->table) ;
            $this->db->where($where)->order_by('created_time', 'desc') ;
            $query = $this->db->get() ;
            // 按照日期重新组装数据
            $result = $this->resort_data_by_date($query->result_array()) ;
            // 生成连续时间段
            $dates = $this->create_sequence_day($param['stime'], $param['etime']) ;
            // 统计起始时间以前的总数
            $count = $this->count(array (
                    'site_id' => $param['site_id'],
                    'province_id' => $city_id,      //区分城市统计wangyang@dachuwang.com
                    'created_time <' => $param['stime']
            )) ;
            // 生成连续时间的数据list
            $data = $this->_together_data($dates, $result, $param, $count, $display_list) ;
        } else {

            $where['created_time >='] = strtotime(date('Y-m-d') . ' 00:00:00') ;
            $where['created_time <='] = time() ;
            $where['site_id'] = $param['site_id'] ;
            $where['province_id = '] = $city_id;  //区分城市统计wangyang@dachuwang.com
            $data[date('Y-m-d')]['customer_new_count'] = $this->count($where) ;
            $data[date('Y-m-d')]['customer_day_count'] = $this->count(array (
                    'site_id' => $where['site_id'],
                    'province_id' => $where['province_id'] //区分城市统计wangyang@dachuwang.com
            )) ;
        }

        return ! empty($data) ? $data : array () ;
    }

    /**
     * 重新连续组装每日客户总数及新增客户数
     *
     * @param array $dates
     * @param array $data
     * @param number $count
     * @param boolean $display_list 是否显示每日新增用户list
     * @return multitype: Ambigous number>
     * @author yuanxiaolin@dachuwang.com
     */
    private function _together_data ($dates = array(), $data = array(), $param = array(), $count = 0, $display_list = false) {

        $merge_data = array () ;
        $init_count = $count ;
        if (empty($dates))
            return array () ;
        for ($i = 0; $i < count($dates); $i ++) {
            if (isset($data[$dates[$i]]) && ! empty($data[$dates[$i]])) {
                $new_count = count($data[$dates[$i]]) ;
                $merge_data[$dates[$i]]['customer_new_count'] = $new_count ;
                $merge_data[$dates[$i]]['customer_day_count'] = $init_count + $new_count ;
                $init_count = $init_count + $new_count ;
                if ($display_list === true) {
                    $merge_data[$dates[$i]]['customer_lists'] = $data[$dates[$i]] ;
                }

            } else {
                $merge_data[$dates[$i]]['customer_new_count'] = 0 ;
                $merge_data[$dates[$i]]['customer_day_count'] = $init_count ;
                if ($display_list === true) {
                    $merge_data[$dates[$i]]['customer_lists'] = array () ;
                }
            }
        }

        return ! empty($merge_data) ? $merge_data : array () ;
    }

    /**
     * 按照线路统计客户数量
     *
     * @author yugang@dachuwang.com
     * @since 2014-03-23
     */
    public function count_by_line ($line_arr) {
        $this->db->select('line_id, count(*) as count') ;
        $this->db->from('t_customer') ;
        $this->db->where_in(array (
                'line_id' => $line_arr
        )) ;
        $this->db->group_by('line_id') ;
        $result = $this->db->get()->result_array() ;
        return $result ;
    }

    /**
     * 生成密码信息
     *
     * @author : yugang@dachuwang.com
     * @version : 1.0.0
     * @since : 2015-03-04
     */
    private function _parse_password ($password, $salt) {
        return md5(md5($password) . $salt) ;
    }

    public function get_count_bywhere ($where) {
        return count($this->db->where($where)->get($this->table)->result_array()) ;
    }

    /**
     *
     * @param int $who 1大厨网 2大果网
     * @return boolean int
     * @author zhangxiao@dachuwang.com
     */
    public function count_total_customer ($who) {
        if ($who != 1 && $who != 2) {
            return false ;
        }
        //城市筛选:wangyang@dachuwang.com
        $city_id  = $this->input->post('city_id');
        $city_id = $city_id ? $city_id : C('open_cities.beijing.id');

        return $this->count(array (
                'status !=' => C('customer.status.invalid.code'),
                "site_id" => $who,
                'province_id' => $city_id   //新增城市筛选 wangyang@dachuwang.com
        )) ;
    }

    /**
     * 批量获取客户信息
     * @method post
     * @param $uids
     * @author yuanxiaolin@dachuwang.com
     */
    public function lists_by_uids($uids = array()){

    	$return = array();
    	if(!empty($uids)){
    		$where['in']['id'] = $uids;
    		$where['status !='] = 0;
    		$result = $this->get_lists(array('id','name','shop_name','username','mobile','address'),$where);

    		if (!empty($result)) {
    			foreach ($result as $key => $value){
    				$return[$value['id']] = $value;
    			}
    		}
    	}
    	return $return;
    }

    /**
     * 获取当前客户的所有子客户
     * @author yugang@dachuwang.com
     * @since 2015-07-30
     */
    public function get_children_ids($uid) {
        $children_ids = [];
        $children_ids[] = $uid;
        $customer = $this->get_one('*', ['id' => $uid]);
        // 子账号只返回自身
        if ($customer['account_type'] == C('customer.account_type.child.value')) {
            return $children_ids;
        }

        // 母账号返回自身以及所有子账号
        $children = $this->get_lists('id', ['parent_mobile' => $customer['mobile'], 'account_type' => C('customer.account_type.child.value'), 'status >' => C('status.common.del')]);
        if (!empty($children)) {
            $children_ids = array_merge($children_ids, array_column($children, 'id'));
        }
        return $children_ids;
    }

    /**
     * 获取母账号id
     * @author yugang@dachuwang.com
     * @since 2015-08-19
     */
    public function get_parent_id($customer_id) {
        $customer = $this->get_one('*', ['id' => $customer_id]);
        // 母账号只返回自身
        if ($customer['account_type'] == C('customer.account_type.parent.value')) {
            return $customer_id;
        }

        $parent = $this->get_one('id', ['mobile' => $customer['parent_mobile'], 'status >' => C('status.common.del')]);
        if (!empty($parent)) {
            return $parent['id'];
        }

        return $customer_id;
    }
}

/* End of file mcustomer.php */
/* Location: :./shared/models/mcustomer.php */
