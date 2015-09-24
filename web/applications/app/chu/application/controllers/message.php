<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Message extends MY_Controller {

    public function __construct () {
        parent::__construct();
        $this->load->library(
            array(
                'Dachu_request'
            )
        );
        $this->load->model(
            array(
                'MMessage_log'
            )
        );
    }


    /**
     * @description 格式化message
     */
    private function _format_messages($msg_list = array()) {
        if(empty($msg_list)) {
            return $msg_list;
        }

        $res = [];
        foreach($msg_list as $msg) {
            $tmp_msg = [];
            $tmp_msg['id'] = intval($msg['id']);
            $tmp_msg['msg_type'] = $msg['msg_type'];
            $tmp_msg['url'] = $msg['url'];
            $tmp_msg['content'] = $msg['content'];
            $tmp_msg['extra'] = json_decode($msg['extra'], TRUE);
            $tmp_msg['receive_time'] = $msg['receive_time'];
            $tmp_msg['title'] = $msg['title'];
            $tmp_msg['status'] = intval($msg['status']);
            $res[] = $tmp_msg;
        }

        return $res;
    }

    /**
     * @description app端获取未读消息
     */
    public function get_unread_messages() {
        $app_uid = empty($_POST['app_uid']) ? 0 : intval($_POST['app_uid']);
        if(!$app_uid) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'user_id为空'
                )
            );
        }

        $app_type_id = C('jpush.app_type.dachu');

        $msg_list = $this->MMessage_log->get_lists(
            '*',
            array(
                'app_type_id' => $app_type_id,
                'app_uid'     => $app_uid,
                'status'      => C('jpush.message_status.init'),
            )
        );
        $msg_list = $this->_format_messages($msg_list);
        if(!empty($msg_list)) {
            $required_fields = parent::$_app_required_fields['message']['get_unread_messages'];
            foreach($msg_list as $k => $val) {
                parent::_get_required_fields($msg_list, $k, $required_fields);
            }
        }
        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => '获取消息成功',
                'lists'  => $msg_list
            )
        );

        $this->_return_json($result);
    }

    /**
     * @description
     */
    public function set_message_status() {
        $this->_filter_app_post();
        $result = $this->format_query(
            '/message/set_message_status',
            $_POST
        );
        $this->_return_json($result);
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description
     */
    private function _filter_app_post() {
        if(!is_array($_POST['lists'])) {
            $_POST['lists'] = json_decode(trim($_POST['lists'], '"'), TRUE);
        }
    }
}

/* End of file message.php */
/* Location: ./application/controllers/message.php */
