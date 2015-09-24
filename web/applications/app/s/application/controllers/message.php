<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Message extends MY_Controller {

    /**
     * 客户端URL属性
     *
     */
    public function __construct () {
        parent::__construct();
        $this->load->library(
            array(
                'jpush'
            )
        );
        $this->load->model(
            array(
                'MMessage_log',
                'MJpush_notice_log',
                'MUser_app_binding',
                'MPush_task_log',
            )
        );
    }

    /**
     * @description 获取未读消息
     * app不用这个接口，
     * 因为只能处理预先写好的字段，字段多了会导致app崩溃
     * @author caochunhui@dachuwang.com
     */
    public function get_unread_messages() {
        $app_uid = empty($_POST['app_uid']) ? 0 : intval($_POST['app_uid']);
        if(!$app_uid) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty user app id'
                )
            );
        }

        //用来区分是大厨、大果、crm的应用
        if(empty($_POST['app_type_id'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty app type id'
                )
            );
        }
        $app_type_id = intval($_POST['app_type_id']);

        $msg_list = $this->MMessage_log->get_lists(
            '*',
            array(
                'app_type_id' => $app_type_id,
                'app_uid'     => $app_uid
            )
        );

        $this->_return_json(
            array(
                'status' => 0,
                'lists'  => $msg_list
            )
        );
    }

    /**
     * @description 客户端获取到极光推送的时间
     * 用来计算时延
     * @author caochunhui@dachuwang.com
     */
    public function set_jpush_received_time() {
        $notice_ids = empty($_POST['notice_ids']) ? [] : intval($_POST['notice_ids']);
        if(!$notice_ids) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty notice_ids'
                )
            );
        }

        if(empty($_POST['receive_time'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty receive_time'
                )
            );
        }

        $receive_time = strtotime($_POST['receive_time']);
        $this->MJpush_notice_log->update_info(
            array(
                'receive_time' => $receive_time
            ),
            array(
                'in' => array(
                    'id' => $notice_ids
                )
            )
        );

        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => 'success'
            )
        );
    }

    /**
     * @description 设置消息状态
     * 1已推到用户 2已读
     * 需要传入客户端推到/阅读的时间
     * @author caochunhui@dachuwang.com
     */
    public function set_message_status() {
        if(empty($_POST) || empty($_POST['lists'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg' => 'list为空'
                )
            );
        }

        $status  = $_POST['status'];
        $rec_arr = $_POST['lists'];
        foreach($rec_arr as $rec_item) {
            $msg_id = $rec_item['id'];
            switch($status) {
            case C('jpush.message_status.sent'): //已收到
                $this->MMessage_log->update_info(
                    array(
                        'status'       => $status,
                        'receive_time' => strtotime($rec_item['receive_time'])
                    ),
                    array(
                        'id' => $msg_id
                    )
                );
                break;
            case C('jpush.message_status.read'): //已读
                $this->MMessage_log->update_info(
                    array(
                        'status'    => $status,
                        'read_time' => strtotime($rec_item['receive_time'])
                    ),
                    array(
                        'id' => $msg_id
                    )
                );
                break;
            default:
                break;
            }
        }

        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => 'set msg status success'
            )
        );
    }


    /**
     *
     * @description 转换用户user_id数组到app_uid数组
     */
    private function _convert_user_id_to_app_uid($user_ids = array(), $app_type_id) {
        $user_app_binding = $this->MUser_app_binding->get_lists(
            'id',
            array(
                'in' => array(
                    'user_id' => $user_ids
                ),
                'app_type_id' => $app_type_id,
                'status' => 1
            )
        );
        $app_uids = array_column($user_app_binding, 'id');
        return $app_uids;
    }

    /**
     * @description 向用户推送消息
     * @param array $user_ids  
     * @param int app_type_id  应用类型 1大厨网 
     * @param string $title 推送标题
     * @param string $content  推送内容
     * @param int $url 客户端URL类型
     * @param string $extra 附加数据
     * @author caochunhui@dachuwang.com
     */
    public function push_messages() {
        if(empty($_POST['user_ids']) || !is_array($_POST['user_ids'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty user_ids'
                )
            );
        }
        if(empty($_POST['app_type_id'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty app_type_id'
                )
            );
        }
        $user_ids    = $_POST['user_ids'];
        $app_type_id = intval($_POST['app_type_id']);

        $title   = empty($_POST['title']) ? '' : $_POST['title'];
        $content = empty($_POST['content']) ? '' : $_POST['content'];
        $url     = empty($_POST['url']) ? '' : $_POST['url'];
        $extra   = empty($_POST['extra']) ? '' : $_POST['extra'];

        $app_uids = $this->_convert_user_id_to_app_uid($user_ids, $app_type_id);
        //调用推送接口
        if(!empty($app_uids)) {
            $msg_arr = [];
            foreach($app_uids as $app_uid) {
                // 消息推送
                $response = $this->jpush->jpush(
                    C('jpush.push_type.all'), array($app_uid), $title, $content, C('jpush.message_type.message'), C('jpush.app_type.dachu')
                );

                // 对推送任务消息记录入库
                $task_id = $this->MPush_task_log->create(
                    array(
                        'push_res'     => $response->json,
                        'created_time' => $this->input->server('REQUEST_TIME'),
                        'updated_time' => $this->input->server('REQUEST_TIME'),
                    )
                );
                $msg = array(
                    'app_type_id'  => $app_type_id,
                    'app_uid'      => $app_uid,
                    'task_id'      => $task_id,
                    'title'        => $title,
                    'content'      => $content,
                    'url'          => C('jpush.url_type.' . $url),
                    'extra'        => $extra,
                    'created_time' => $this->input->server('REQUEST_TIME'),
                    'updated_time' => $this->input->server('REQUEST_TIME'),
                );
                $msg_arr[] = $msg;
            }

            //一次性插入
            $this->MMessage_log->create_batch(
                $msg_arr
            );
        }

        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => 'success'
            )
        );

    }

    /**
     * @description 广播消息，广告之类的东西
     * @author caochunhui@dachuwang.com
     */
    public function broadcast_message() {
        $app_type_id = empty($_POST['app_type_id']) ? 0 : intval($_POST['app_type_id']);
        if(!$app_type_id) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty app_type_id'
                )
            );
        }

        if(empty($_POST['message'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'empty message'
                )
            );
        }

        $title   = empty($_POST['title'])   ? '' : $_POST['title'];
        $content = empty($_POST['content']) ? '' : $_POST['content'];
        $url     = empty($_POST['url'])     ? '' : $_POST['url'];
        $extra   = empty($_POST['extra'])   ? '' : $_POST['extra'];

        $message = $_POST['message'];

        $app_bindings = $this->user_app_binding->get_lists(
            'id',
            array(
                'app_type_id' => $app_type_id,
                'status'      => 1,
            )
        );
        $app_uids = array_column($app_bindings, 'id');

        //调用推送接口
        if(!empty($app_uids)) {
            $msg_arr = [];
            foreach($app_uids as $app_uid) {
                $msg = array(
                    'app_type_id'  => $app_type_id,
                    'app_uid'      => $app_uid,
                    'title'        => $title,
                    'content'      => $content,
                    'url'          => $url,
                    'extra'        => $extra,
                    'created_time' => $this->input->server('REQUEST_TIME'),
                    'updated_time' => $this->input->server('REQUEST_TIME'),
                );
                $msg_arr[] = $msg;
            }

            //一次性插入
            $this->MMessage_log->create_batch(
                $msg_arr
            );
        }

        $this->_return_json(
            array(
                'status' => 0,
                'msg'    => 'success'
            )
        );
    }

    public function jpush_test() {

        $alias = array();
        $this->load->library(
            array(
                'Jpush'
            )
        );
        $message = 'oh cai';
        $title = "nonono";
        $result = $this->jpush->jpush(
            C('jpush.push_type.all'), $alias, $title, $message, C('jpush.message_type.message'), C('jpush.app_type.dachu')
        );
        log_message('error', var_export($result, true));
        //$push_type = 1, $alias = array(), $notification = 'test', $msg_type = 1, $app_type_id = 1, $platform_id = 0

    }
}

/* End of file message.php */
/* Location: ./application/controllers/message.php */
