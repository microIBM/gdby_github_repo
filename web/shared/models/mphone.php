<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 手机校验model
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class MPhone extends MY_Model {
    use MemAuto;

    private $table = 'phone';

    public function __construct() {
        parent::__construct($this->table);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 检测验证码
     */
    public function check_code($mobile, $vcode) {
        $this->db->where('mobile', $mobile);
        $this->db->where('code', $vcode);
        $create_time = $this->input->server("REQUEST_TIME") - 1800;
        $this->db->where('created_time >=', $create_time);
        $this->db->order_by('created_time', 'desc');
        $this->db->limit(1);
        $res = $this->db->get($this->table)->result_array();
        if($res) {
            $info = array(
                'status'    => TRUE,
                'message'   => '校验码正确'
            );
        }else {
            $info = array(
                'status'    => FALSE,
                'message'   => '校验码错误，请重新获取'
            );
        }
        return($info);
    }
}
/* End of file mphone.php */
/* Location: :./application/models/mphone.php */
