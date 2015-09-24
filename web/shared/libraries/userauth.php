<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 权限接口控制
 * @author: caiyilong@ymt360.com
 * @version: 1.0.0
 * @since: 2015-01-17
 */
class UserAuth {

    private $_session_uid = "authtoken";
    private $_customer_prefix = "customer_";
    private $_user_prefix = "user_";
    private $_remember_me = "remember";
    private $_privilege = "privilege";
    /**
     * 构造函数
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-17
     */
    public function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->library(array(
            'encrypt',
        ));
        $this->CI->load->model(array(
            'MUser',
            'MCustomer',
            'MRole',
            'MPrivilege',
            'MUser_action',
            'MLocation',
        ));
        $this->err = C("userauth");
        $this->_set_error_info($this->err['default']);
    }

    /**
     * 根据用户登录，初始cookie, session
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-17
     */
    public function login($mobile, $password, $is_customer = TRUE, $site = '', $category = '') {
        // 查询用户
        if($is_customer) {
            $user_model = $this->CI->MCustomer;
            $fields = 'mobile, password, status, salt, id, name, role_id, province_id, city_id, county_id, site_id, customer_type, is_active, shop_name, account_type, billing_cycle';
        } else {
            $user_model = $this->CI->MUser;
            $fields = 'mobile, password, status, salt, id, name, role_id, province_id, city_id, county_id, site_id, is_active';
        }
        $data = $user_model->get_one(
            $fields,
            ['mobile' => $mobile, 'status !=' => C('status.common.del')]
        );
        if(!$data) {
            return $this->_return_res($this->err['not_found']);
        }
        // 判断是否需要审核
        if($data['status'] == C('status.user.pending') || $data['is_active'] == C('customer.status.invalid.code')) {
            return $this->_return_res($this->err['not_actived']);
        }
        // 判断是否已经被禁用
        if($data['status'] == C('status.common.disabled')) {
            return $this->_return_res($this->err['disabled']);
        }

        // 检查密码
        if($data['password'] !== $this->_parse_password($password, $data['salt'], $category)) {
            return $this->_return_res($this->err['invalid_password']);
        }

        // 设置登录状态
        $this->_set_current($data, $is_customer);
        $res = $this->err['success'];
        // 返回用户角色类型，客户端根据type显示不同的页面
        $res['token'] = $this->_get_token($is_customer);
        $res['access'] = $this->_get_permission($is_customer);
        $city_name = '';
        if ($data['province_id']) {
            $location_info = $this->CI->MLocation->get_one(
                'name',
                array(
                    'id' => $data['province_id']
                )
            );
            $city_name = $location_info ? end($location_info) : '';
        }
        $res['info'] = array(
            'type'          => $data['role_id'],
            'id'            => $data['id'],
            'mobile'        => $data['mobile'],
            'city_id'       => $data['province_id'],
            'site_id'       => $data['site_id']
       );
        if ($is_customer) {
            $res['info']['name']      = $data['name'];
            $res['info']['shop_name'] = $data['shop_name'];
            $res['info']['city_name'] = $city_name;
            $res['info']['billing_cycle'] = $data['billing_cycle'];
        }
        if(!empty($data['customer_type'])) {
            $res['info']['customer_type'] = $data['customer_type'];
        }
        if(!empty($data['account_type'])) {
            $res['info']['account_type'] = $data['account_type'];
        }
        return $this->_return_res($res);
    }

    /**
     * 获取当前用户信息
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-17
     */
    public function current($is_customer = TRUE) {
        static $cur = FALSE;
        $client_ip = $this->CI->input->ip_address();
        if(!$cur) {
            $uid = $this->CI->session->userdata($this->_get_session_key($is_customer));
            if(!empty($uid)) {
                $cur = $this->_get($uid, NULL, $is_customer);
                $cur['ip'] = $client_ip;
                return $cur;
            }
            // 选择了自动登录的情况
            if($remember_data = $this->CI->input->cookie($this->_remember_me)) {
                $remember_data = $this->CI->encrypt->decode($remember_data);
                $remember_data = json_decode($remember_data);
                if(!empty($remember_data->uid)) {
                    $cur = $this->_get($remember_data->uid, NULL, $is_customer);
                    if(!empty($cur)) {
                        $cur['ip'] = $client_ip;
                        if(empty($remember_data->pwd) || $remember_data->pwd !== $cur['password']) {
                            $cur = FALSE;
                        }
                        // 检测通过，直接自动登录，并记录登录行为
                        if(!empty($cur)) {
                            $this->_set_current($cur, $is_customer);
                        }
                    }
                }
            }
        }
        return $cur;
    }


    /**
     * 退出登录
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-19
     */
    public function logout($is_customer = TRUE) {
        $user = $this->current($is_customer);
        if(!empty($user)) {
            $user_id = $user['id'];
        }
        $this->CI->input->set_cookie($this->_remember_me, '', '');
        $this->CI->session->unset_userdata($this->_get_session_key($is_customer));
        return TRUE;
    }

    private function _get_session_key($is_customer = TRUE) {
        $prefix = $is_customer ? $this->_customer_prefix : $this->_user_prefix;
        return $prefix . "_" . $this->_session_uid;
    }

    public function check_password($password, $is_customer = TRUE) {
        $user = $this->current($is_customer);
        return $this->_parse_password($password, $user['salt']) === $user['password'];
    }

    /**
     * 检查请求合法性
     * @author: yugang@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-22
     */
    public function check_validation($resource, $operation, $module = '', $is_customer = TRUE) {
        /*static $all_privileges = null;
        if(!$all_privileges) {
            // 加载所有权限列表到内存中
            $all_privileges = array();
            $result = $this->CI->MPrivilege->get_lists('module, resource, operation');
            var_dump($result);
            foreach ($result as $k => $v) {
                $all_privileges[] = implode('.', $v);
            }
        }*/
        // $request = implode('.', array($module, $resource, $operation));
        // 如果请求的是不在权限控制范围内的公共方法，无需验证，直接通过
        /*if (!in_array($request, $all_privileges)) {
            return C('status.auth.allow');
        }*/

        // 检查是否已登录
        $cur = $this->current($is_customer);
        if(empty($cur)) {
            return C('status.auth.login_timeout');
        }
        // 从session中获取用户权限
        // $privilege = $this->CI->session->userdata($this->_privilege);
        // 从Memcache中获取权限
        $privilege = $this->_get_permission($is_customer);
        if(empty($privilege)) {
            return C('status.auth.forbidden');
        }
        // $privilege = json_decode($privilege, TRUE);
        // var_dump($privilege);
        // 验证用户请求是否在权限范围内
        foreach ($privilege as $k => $v) {
            if(strtoupper($module) == strtoupper($v['module'])
                && strtoupper($resource) == strtoupper($v['resource'])
                && strtoupper($operation) == strtoupper($v['operation'])){
                    return C('status.auth.allow');
                }
        }

        return C('status.auth.forbidden');
    }

    /**
     * 生成随机密码
     */
    public function get_rand_pass($len = 6) {
        $password = '';
        $chars = '123456789';
        while(strlen($password) < $len) {
            $password .= substr($chars, (mt_rand() % strlen($chars)), 1);
        }

        return $password;
    }

    /**
     * 获取用户信息
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-19
     */
    private function _get($user_id, $status = null, $is_customer = TRUE) {
        $where['id'] = $user_id;
        if(!empty($status)) {
            $where['status'] = $status;
        } else {
            $where['status !='] = C("status.user.del");
        }
        if($is_customer) {
            $user_model = $this->CI->MCustomer;
        } else {
            $user_model = $this->CI->MUser;
        }
        return $user_model->get_one('*', $where);
    }

    /**
     * 设置用户信息
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-17
     */
    private function _set_current($user = NULL, $is_customer = TRUE, $remember = TRUE) {
        if(is_array($user)) {
            $this->CI->session->set_userdata($this->_get_session_key($is_customer), $user['id']);
            if($remember) {
                $remember_data = json_encode(array('uid' => $user['id'], 'pwd' => $user['password']));
                $remember_cookie = array(
                    'name'   => $this->_remember_me,
                    'value'  => $this->CI->encrypt->encode($remember_data),
                    'expire' => 86400 * 31
                );
                $this->CI->input->set_cookie($remember_cookie);
            }
            // 记录登录行为
            // $this->CI->useraction->insert_login_action($user['id']);
        } else if(is_null($user)) {
            $this->CI->input->set_cookie($this->_remember_me, '', '');
            $this->CI->session->unset_userdata($this->_get_session_key($is_customer));
        }
    }

    private function _get_token($is_customer = TRUE) {
        $cur = $this->current($is_customer);
        if(empty($cur)) {
            return FALSE;
        }
        return md5($this->CI->session->userdata($this->_get_session_key()) . "_" . $cur['salt']);
    }

    /**
     *  获取用户权限列表
     *  @author yugang@ymt360.com
     *  @since 2015-1-21
     *  @return 当前登录用户的权限列表
     */
    private function _get_permission($is_customer = TRUE) {
        // 先判断是否登录
        $cur = $this->current($is_customer);
        if(empty($cur)) {
            return FALSE;
        }
        // TODO 加入Memcache机制，每次先尝试从缓存读取，读取成功存入缓存
        // 查询用户角色权限ID
        $data = $this->CI->MRole->get_one('*', array('id' => $cur['role_id']));
        if(empty($data)) {
            return FALSE;
        }

        // 根据权限ID获取权限列表
        $where['in'] = array('id' => explode(',', $data['pri_id']));
        $where['status'] = 1;
        $privilege = $this->CI->MPrivilege->get_lists('id, module, resource, operation', $where);
        if(empty($privilege)) {
            return FALSE;
        }
        return $privilege;
    }

    /**
     * 设置错误信息
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-17
     */
    private function _set_error_info($info) {
        if(!empty($info)) {
            $this->res = array(
                'status' => $info['id'],
                'msg' => $info['msg'],
            );
            if(isset($info['type'])) {
                $this->res['type'] = $info['type'];
            }
            if(isset($info['token'])) {
                $this->res['token'] = $info['token'];
            }
            if(isset($info['access'])) {
                $this->res['access'] = $info['access'];
            }
            if(isset($info['info'])) {
                $this->res['info'] = $info['info'];
            }
        }
    }

    /**
     * 返回授权结果
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-17
     */
    private function _return_res($info) {
        if(!empty($info)) {
            $this->_set_error_info($info);
        }
        return $this->res;
    }

    /**
     * 生成密码信息
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-17
     */
    private function _parse_password($password, $salt, $category) {
        if($category == 'app') {
            return md5($password. $salt);
        }
        return md5(md5($password) . $salt);
    }


}

/* End of file userauth.php */
/* Location: :./shared/libraries/userauth.php  */
