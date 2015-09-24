<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Bd_location extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model([
            'MUser_location',
        ]);
    }

    public function record_location() {
        if(!isset($_POST['id'])) {
            $this->_assemble_error('缺少id');
        }
        if(!isset($_POST['points'])) {
            $this->_assemble_error('缺少定位点信息');
        }
        $data = [];
        $now = time();
        $user_id = intval($_POST['id']);
        $extra_info = ['user_id' => $user_id, 'created_time' => $now, 'updated_time' => $now];
        foreach($_POST['points'] as $point) {
            $data[] = array_merge($point, $extra_info);
        }
        $rows = $this->MUser_location->create_batch($data);
        if($rows) {
            $this->_assemble_result('success', [
                'affected_rows' => $rows
            ]);
        } else {
            $this->_assemble_error('数据写入失败');
        }
    }

    private function _assemble_result($msg, $list) {
        $this->_return_json([
            'status' => C('status.req.success'),
            'msg' => $msg,
            'list' => $list
        ]);
    }

    private function _assemble_error($msg) {
        $this->_return_json([
            'status' => C('status.req.failed'),
            'msg' => $msg,
        ]);
    }
}
