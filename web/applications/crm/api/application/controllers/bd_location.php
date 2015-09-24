<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
 * @description CRM对账管理
 * @author liudeen@dachuwang.com
 * @since 2015-07-18
 */

class Bd_location extends MY_Controller
{
    public function __construct() {
        parent::__construct();
        $this->load->model(['MUser']);
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
            'msg' => $msg
        ]);
    }

    public function record_location() {
        $_POST = json_decode(trim($_POST['location'], '"'), true);
        if(!isset($_POST['id']) || empty($_POST['id'])) {
            $cur = $this->userauth->current(FALSE);
            if(isset($cur['id'])) {
                $_POST['id'] = $cur['id'];
            } else {
                $this->_assemble_error('缺少id');
            }
        }
        if(!isset($_POST['points'])) {
            $this->_assemble_error('缺少定位点信息');
        }
        //转换时间为unix_timestamp
        foreach($_POST['points'] as &$point) {
            if(isset($point['time'])) {
                $point['time'] = strtotime($point['time']);
            }
        }
        unset($point);
        $result = $this->format_query('/bd_location/'.__FUNCTION__, $_POST);
        if(!$result) {
            $this->_assemble_error('S域接口调用失败');
        } else if($result['status'] != C('status.req.success')) {
            $this->_assemble_error($result['msg']);
        }
        $this->_assemble_result($result['msg'], $result['list']);
    }

}

/* End of file bd_location.php */
/* Location: :./application/controllers/bd_location.php */
