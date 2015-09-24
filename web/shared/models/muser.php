<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 用户操作model
 * @author: yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-04
 */
class MUser extends MY_Model {
    use MemAuto;

    private $table = 't_user';
    protected $_salt  = NULL;

    public function __construct() {
        parent::__construct($this->table);
    }

    /**
     * 以函数返回值形式返回用户信息(不包含密码)
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     */
    public function get_user_info($query) {
        $user_info = $this->get_one('*', $query);
        return $user_info;
    }

    /**
     * 检测手机号是否唯一
     * @author yugang@dachuwang.com
     * @since 2015-03-04
     * @return 检测结果
     */
    public function check_mobile_unique($mobile) {
        if($this->get_by('mobile', $mobile)){
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 修改用户状态
     * @author yugang@dachuwang.com
     * @since 2014-03-04
     */
    public function toggle_status($uid, $status) {
        return $this->update_by('id', $uid, array('status' => $status));
    }

    /**
     * 重置密码
     * @author yugang@dachuwang.com
     * @description 重置用户密码为手机号后6位
     * @since 2014-03-04
     */
    public function reset_password($uid, $password) {
        $user = $this->get_one('*', array('id' => $uid));
        if (!$user) {
            return FALSE;
        }
        $new_password = $this->_parse_password($password, $user['salt']);
        $result = $this->update_by('id', $uid, array('password' => $new_password));
        return $result;
    }

    /**
     * 生成密码信息
     * @author: yugang@dachuwang.com
     * @version: 1.0.0
     * @since: 2015-03-04
     */
    private function _parse_password($password, $salt) {
        return md5(md5($password) . $salt);
    }

    public function get_db_group($where_in, $select) {
        return $this->db->select($select)->where_in('role_id', array(12, 13))->where_in('dept_id',$where_in)->get($this->table)->result_array();
    }
}

/* End of file muser.php */
/* Location: :./shared/models/muser.php */
