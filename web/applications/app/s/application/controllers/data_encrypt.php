<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 数据加密验证
 * @author changshaoshuai@dachuwang.com
 * @since 2015-07-03
 */
class Data_encrypt extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('MUser_app_binding');

    }

    public function check_sign() {
        $data = $_POST;
        $return = array(
            'status' => C('status.req.success')
        );
        $id = empty($data['user_app_id']) ? 0 : $data['user_app_id'];
        $res = $this->MUser_app_binding->get_one(
            '*',
            array('id' => $id)
        );
        if(empty($res)) {
            $return['status'] = C('status.req.failed');
            $this->_return_json($return);
        }
        $param = '';
        ksort($data);
        foreach($data as $key => $val) {
            if($key == 'user_sign')
                continue;
            $param .= $key. '='. $val. '&';
        }
        $param = $param .'key='. $res['user_encrypt'];
        if(md5($param) != $data['user_sign']) {
            $this->_return_json(
                array(
                    'status' => -10,
                    'msg'    => '数据加密验证失败！'
                )
            );
        }
        $this->_return_json($return);
    }
}
