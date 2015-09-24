<?php use Push\Push;

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Message extends MY_Controller {

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
                        'read_time' => strtotime($rec_item['read_time'])
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
     * 根据用户id和客户端Id, 按照设备平台分组, 并获取了接口关心的关键字段
     * @param array $user_ids
     * @param int $app_type_id
     * @return array 包含不同平台的接口关心的字段数组
     */
    private function _filter_app_uid_by_platform(Array $user_ids, $app_type_id = 1)
    {
//        $user_ids = $this->input->post('user_ids');
        $result = $this->MUser_app_binding->get_lists(
            array('id', 'platform', 'device_token'),
            array(
            'in' => array(
                'user_id' => $user_ids
            ),
            'app_type_id' => $app_type_id,
            'status' => 1
        ));

        // 对数据分组，按照安卓平台和苹果平台索引
        foreach($result as $no => $app_uid){
            if($app_uid['platform'] == C('jpush.platform.ios') || !empty($app_uid['device_token'])) {
                $data['ios'][] = $app_uid;
            } else {
                $data['android'][] = $app_uid['id'];
            }
        }
        //        log_message('debug', var_export($data, true));
        //        $this->iso_push($data['ios']);

        return $data;
    }

    /**
     * @description 向用户推送消息
     * @internal param array $user_ids
     * @internal param app_type_id $int 应用类型 1大厨网
     * @internal param string $title 推送标题
     * @internal param string $content 推送内容
     * @internal param int $url 客户端URL类型
     * @internal param string $extra 附加数据
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

        // select user_ids group by platform
        $app_uids = $this->_filter_app_uid_by_platform($user_ids, $app_type_id);
        // check empty
        if(empty($app_uids)) {
            $this->_return_json(
                array(
                'status' => -2,
                'msg_id' => 0,
                'msg'    => "Sorry, I can't find bind relation between app and user_id",
                )
            );
        }

        // android pushed by default method
        if(isset($app_uids['android']) && !empty($app_uids['android'])) {
            $msg_ids = $this->push_to_android($app_uids['android'], $app_type_id);
        }

        // ios pushed by another new method
        if(isset($app_uids['ios']) && !empty($app_uids['ios'])) {
            $this->push_to_ios($app_uids['ios'], $app_type_id);
        }

        // todo result statics and message_id = android + ios ?
        $this->_return_json(array(
                'status' => 0,
                'msg_id' => isset($msg_ids) ? $msg_ids : 0,
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


    /**
     * 推送数据统计情况接口
     * @param msg_ids array 消息ID  极光推送返回ID
     * @return array
     * @author fengzongbao@dachuwang.com
     */
    public function stats()
    {
        if(empty($_POST['msg_ids'])) {
            $this->_return_json(array(
                'status' => -1,
                'msg'    => 'msg_ids empty'
            ));
        }

        $msg_ids = $_POST['msg_ids'];
        $result = [];
        foreach($msg_ids as $msg_id) {
            $result[$msg_id]['receive'] = $this->MMessage_log->count(array(
                'receive_time !=' => 0 ,
                'msg_id' => $msg_id
            ));

            $result[$msg_id]['read'] = $this->MMessage_log->count(array(
                'status' => 2,
                'msg_id' => $msg_id
            ));
        }

        $this->_return_json(array(
            'status' => 0,
            'list'   => $result
        ));
    }

    /**
     * 安卓推送 拆分自之前的推送逻辑
     * @param $app_uids
     * @param $app_type_id
     * @return array $msg_ids
     */
    private function push_to_android($app_uids, $app_type_id)
    {
        $title   = empty($_POST['title']) ? '' : $_POST['title'];
        $content = empty($_POST['content']) ? '' : $_POST['content'];
        $url     = empty($_POST['url']) ? '' : $_POST['url'];
        $extra   = json_encode($_POST['extra']);
        if(!empty($app_uids)) {
            // 切分appuid，每次请求极光推送的客户为800条
            $sliced_array = array_slice($app_uids, 0, 800);
            $offset = 0;
            $msg_arr = [];
            $msg_ids = [];
            while (!empty($sliced_array)) {
                // 消息推送
                $response = $this->jpush->jpush(
                    C('jpush.push_type.all'), $sliced_array, $title, $content, C('jpush.message_type.message'), C('jpush.app_type.dachu')
                );

                // 对推送任务消息记录入库
                $task_id = $this->MPush_task_log->create(
                    array(
                        'push_res'     => $response->json,
                        'created_time' => $this->input->server('REQUEST_TIME'),
                        'updated_time' => $this->input->server('REQUEST_TIME'),
                    )
                );

                foreach($app_uids as $app_uid) {
                    $msg = array(
                        'app_type_id'  => $app_type_id,
                        'platform'     => C('jpush.platform.android'),
                        'app_uid'      => $app_uid,
                        'task_id'      => $task_id,
                        'msg_id'       => $response->msg_id,
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
                $msg_ids[] = $response->msg_id;
                $offset += 180;
                $sliced_array = array_slice($app_uids, $offset, 800);
            }

        } else {
            $msg_ids = array();
        }

        return $msg_ids;
    }

    /**
     * @param array $app_uid_relations
     * @param $app_type_id
     * @internal param array $device_tokens 苹果的设备编号
     * @return int 推送数量统计
     */
    private function push_to_ios(Array $app_uid_relations, $app_type_id)
    {
        $title   = empty($_POST['title']) ? '' : $_POST['title'];
        $content = empty($_POST['content']) ? '' : $_POST['content'];
        $url     = empty($_POST['url']) ? '' : $_POST['url'];
        $extra   = $_POST['extra'];

        // the payload message body
        $message = array(
            'aps' => array(
                'alert' => $title . time(),
                'sound' => 'default',
//                'badge' => 1   // app 未做处理，暂时不传
            ),
            'content' => $content,
            'url'     => C('jpush.url_type.' . $url),
            'extra'   => $extra,
        );
        // 证书密码配置化
        $certificate = '';
        $environment = '';
        if (defined('ENVIRONMENT'))
        {
            switch (ENVIRONMENT)
            {
                case 'development':
                    $certificate = BASEPATH . "../shared/config/development/ck.pem";
                    $environment = Push::ENVIRONMENT_SANDBOX;
                    break;

                case 'production':
                    $certificate = BASEPATH . "../shared/config/ck.pem";
                    $environment = Push::ENVIRONMENT_PRODUCTION;
                    break;
            }
        } else {
            $certificate = BASEPATH . "../shared/config/ck.pem";
            $environment = Push::ENVIRONMENT_PRODUCTION;
        }

        $push = new Push($environment, $certificate, C('jpush.apns.password'));
        $push->connect();

        foreach($app_uid_relations as  $app_uid_relation) {
            $msg = array(
                'app_type_id'  => $app_type_id,
                'app_uid'      => $app_uid_relation['id'],
                'platform'     => C('jpush.platform.ios'),
//                'task_id'      => $task_id,
//                'msg_id'       => ,
                'title'        => $title,
                'content'      => $content,
                'url'          => C('jpush.url_type.' . $url),
                'extra'        => json_encode($extra),
                'created_time' => $this->input->server('REQUEST_TIME'),
                'updated_time' => $this->input->server('REQUEST_TIME'),
            );

            $msg_id = $this->MMessage_log->create($msg);
            $message['id'] = $msg_id;

            $push->send($app_uid_relation['device_token'], $message);
        }

        $push->disconnect();

        // 批量入库
        // TODO return statics of result
        return count($app_uid_relations);
    }
}

/* End of file message.php */
/* Location: ./application/controllers/message.php */
