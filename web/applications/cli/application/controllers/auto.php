<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 定时自动上下线
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2015-5-13
 */
class Auto extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }

    public function advertise() {
        // 当前时间
        $lists = $this->MAds->get_lists('status, online_time, offline_time', array('status' => C('status.common.unverified')));
        if($lists) {
            foreach($lists as $list) {
                $status = $this->_check_valid_time($list);
                if($status == $list['status']) {
                    $this->MAds->update_info(array('status' => $status), array('id' => $list['id']));
                }
            }
        }
    }
    // 
    public function subject() {
        // 当前时间
        $subjects = $this->MSubject->get_lists('status, online_time, offline_time', array('status' => C('status.common.unverified')));
        if($subjects) {
            foreach($subjects as $subject) {
                $status = $this->_check_valid_time($subject);
                if($status == $subject['status']) {
                    $this->MSubject->update_info(array('status' => $status), array('id' => $subject['id']));
                }
            }
        }
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 检测时间范围
     * status 0 已下线  1 进行中 2待上线  3 已结束
     */
    private function _check_valid_time($data) {
        $status = 2;
        $current_time = $this->input->server('REQUEST_TIME');
        if($current_time >= $data['online_time'] && $current_time <= $data['offline_time']) {
            $status = 1;
        } else if($current_time > $data['offline_time']) {
            $status = 3;
        }
        return $status;
    }
}

/* End of file auto.php */
/* Location: ./application/controllers/auto.php */
