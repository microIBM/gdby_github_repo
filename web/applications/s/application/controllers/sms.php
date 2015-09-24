<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 消息系统控制器
 * @author : caiyilong@ymt360.com
 * @version : 1.0.0
 * @since : 2014-10-23
 */
class Sms extends MY_Controller {

    /**
     * 构造函数
     */
    public function __construct() {
        parent::__construct();
        $this->load->library(
            array(
                'Smsapi_dachu',
                'Smsapi_daguo',
                'beanstalk',
            )
        );
        $this->load->model(
            array(
                'MSms_log',
                'MJob',
            )
        );
        $this->type = C("sms_type");
    }


    /**
     *
     * @author caochunhui@dachuwang.com
     * @description 发送短信新接口，使用亿美的服务
     */
    public function send_sms() {
        //判断发短信的post是否合法
        if(!$_POST) {
            $this->_return_json(array('status' => -1, 'msg' => "JSON parse failed!"));
        }

        if(empty($_POST['mobile']) || empty($_POST['content'])) {
            $this->_return_json(array('status' => -1, 'msg' => "Invalid data!"));
        }

        $mobile    = $_POST['mobile'];
        $sms_type  = $_POST['sms_type'];
        $corp_sign = C('sms_new.marketing.dachu_sign');
        $content   = $corp_sign . $_POST['content'];
        $delay     =  !empty($_POST['delay']) ? $_POST['delay'] : 0; 

        if(!is_array($mobile)) {
            $mobile = array(
                $mobile
            );
        }
        $tmp_mobile_arr = array_slice($mobile, 0, 180);
        $offset = 0;
        $job_array = array();
        while(!empty($tmp_mobile_arr)) {
            $post_mobile = implode(',', $tmp_mobile_arr);

            // 投放队列
            $data = array(
                'phone'     => $post_mobile,
                'message'   => $content,
                'sms_type'  => $sms_type,
            );
            if (C('sms_new.normal.switch') == 'off') {
                $job_id = 0;
            } else {
                $job_id = $this->MJob->create_job('sms', $data, 1024, $delay);
            }

            $logs = [];
            foreach($tmp_mobile_arr as $item) {
                $logs[] = array(
                    'type_id'       => $sms_type,
                    'mobile'        => $item,
                    'content'       => $content,
                    'status'        => 1,
                    'job_id'        => $job_id,
                    'created_time'  => $this->input->server("REQUEST_TIME"),
                );
            }
            $job_array[] = $job_id;

            //批量插入短信发送log
            $res = $this->MSms_log->create_batch($logs);
            $offset += 180;
            $tmp_mobile_arr = array_slice($mobile, $offset, 180);
        }
        // 插入队列正常  返回正常结果
        $this->_return_json(
            array(
                'status' => 0,
                'job_id' => $job_array,
                'msg'    => 'wait for sending ...'
            )
        );
    }


    /**
     * 供worker调用的短信发送接口
     * @param string $phone 供亿美接口使用的手机号参数 多个手机号用逗号隔开
     * @param string $content 短信发送内容
     * @param int $sms_type  短信类型
     * @author fengzongbao@dachuwang.com
     */
    public function worker_sms_send(){
        if(empty($_POST['phone']) || empty($_POST['message']) || empty($_POST['sms_type'] || $_POST['job_id'])) {
            $this->_return_json(array(
                'status' => -1,
                'msg'    => 'Invalid message',
            ));
        }
        $phone    = $_POST['phone'];
        $content  = $_POST['message'];
        $sms_type = $_POST['sms_type'];
        $job_id   = $_POST['job_id'];

        $password   = C('sms_new.normal.password');
        $cdkey      = C('sms_new.normal.cdkey');
        $post_url   = C('sms_new.normal.post_url');
        $switch     = C('sms_new.normal.switch');

        // 营销
        if(!empty($sms_type) && $sms_type == C('sms_new.marketing.code')) {
            $password   = C('sms_new.marketing.password');
            $cdkey      = C('sms_new.marketing.cdkey');
            $post_url   = C('sms_new.marketing.post_url');
            $switch     = C('sms_new.marketing.switch');
        }

        // 合法
        $this->load->library(
            array(
                'Dachu_request'
            )
        );

        $data = array(
            'cdkey'     => $cdkey,
            'password'  => $password,
            'phone'     => $phone,
            'message'   => $content,
            'addserial' => C('site.dachu'),
        );

        // 短信开关打开才会调用亿美接口
        if($switch == 'on') {
            $response = $this->dachu_request->get($post_url, $data);
            $response_arr = json_decode(json_encode(simplexml_load_string($response['res'])), TRUE);
        } else {
            // 开关关闭，始终认为亿美返回正常
            $response_arr = array(
                'error' => 0,
                'message' => ['sms switch closed'],
            );
        }

        // 保存在数据库里的响应内容，如果有错误码，存错误码，出现其它情况直接存错误信息
        $response_to_save = '';
        if($response_arr && isset($response_arr['error'])) {
            $response_to_save = $response_arr['error'];
        } else {
            $response_to_save = $response['res'];
        }

        // 短信接口开关如果switch为关闭状态的话，那么只插数据库，不调用亿美接口
        if($switch != 'on') {
            $response_to_save = 'sms switch closed';
        }

        $logs = [];
        $phone_array  = explode(',', $phone);
        foreach($phone_array as $item) {
            $logs[] = array(
                'job_id'        => $job_id,
                'send_response' => $response_to_save,
                'updated_time'  => $this->input->server("REQUEST_TIME"),
            );
        }

        // 批量更新短信发送log
        $res = $this->MSms_log->update_batch($logs, 'job_id');
        // update job message
        $this->MJob->update_job($response_arr, $job_id);

        if($response_arr && isset($response_arr['error']) && $response_arr['error'] == 0) {
            $this->_return_json(
                array(
                    'status'  => 0,
                    'message' => 'sms send from worker success'
                )
            );
        } else {
            $this->_return_json(
                array(
                    'status'  => $response_arr['error'],
                    'message' => $response_arr['message'],
                )
            );
        }
    }


    /**
     * 从发送队列中撤销本次发送任务, 以后可以管理到队列管理中
     */
    public function pull_sms_job()
    {
        if(empty($_POST['job_id'])) {
            $this->_return_json(
                array(
                    'status' => -1,
                    'message' => 'job_id empty'
                )
            );
        }
        $job_id = $_POST['job_id'];

        try {
            $this->MJob->job_delete($job_id);
        } catch (Exception $e) {
            // todo 删除任务失败，记录日志
            var_dump($e);
        }

        $this->_return_json(
            array(
                'status' => 0,
                'job_id' => $job_id,
                'message ' => 'job to send sms is deleted'
            )
        );
    }

    /**
     * 通知短信发送接口
     * @param array $mobiles 手机号列表
     * @param array $contents 内容列表
     */
    public function send_notice() {
        $_POST['sms_type'] = C('sms_new.marketing.code');
        return $this->send_sms();
        $j = $_POST;
        if(!$j) {
            $this->_return_json(array('status' => -1, 'msg' => "JSON parse failed!"));
        }
        if(empty($j) || empty($j['mobile']) || empty($j['content'])) {
            $this->_return_json(array('status' => -1, 'msg' => "Invalid data!"));
        }
        $mobiles = [];
        if(is_array($j['mobile'])) {
            $mobiles = $j['mobile'];
        } else {
            $mobiles = [
                $j['mobile']
            ];
        }

        $site_id = isset($j['site']) ? intval($j['site']) : C('site.dachu');
        if($site_id  == C('site.dachu')) {
            $res = $this->smsapi_dachu->send_marketing("notice", $mobiles, $j['content']);
        } else if($site_id == C('site.daguo')) {
            $res = $this->smsapi_daguo->send_marketing("notice", $mobiles, $j['content']);
        } else {
            $res = $this->smsapi_dachu->send_marketing("notice", $mobiles, $j['content']);
        }
        //$res = "1,201410242024";
        $res_arr = explode(",", $res);
        if($res_arr[0] == 1) {
            if(is_array($j['mobile'])) {
                $mobiles = $j['mobile'];
            } else {
                $mobiles = explode(",", $j['mobile']);
            }
            $content = $j['content'];
            $datas = array();
            foreach($mobiles as $mobile) {
                $mobile = trim($mobile);
                $datas[] = array(
                    'type_id'      => $this->type['notice'],
                    'mobile'       => $mobile,
                    'content'      => $content,
                    'status'       => 1,
                    'created_time' => $this->input->server("REQUEST_TIME"),
                    'updated_time' => $this->input->server("REQUEST_TIME"),
                );
            }
            $res = $this->MSms_log->add_batch($datas);
            $this->_return_json(array('status' => 0, 'msg' => "发送短信成功"));
        }else{
            $this->_return_json(array('status' => $res_arr[0], 'msg' => "Sms send failed!"));
        }
    }


    /**
     * 验证码短信发送接口
     * @param array $mobiles 手机号列表
     * @param array $contents 内容列表
     */
    public function send_captcha() {
        $_POST['sms_type'] = C('sms_new.normal.code');
        return $this->send_sms();
        $j = $_POST;
        if(!$j) {
            $this->_return_json(array('status' => -1, 'msg' => "JSON parse failed!"));
        }
        if(empty($j) || empty($j['mobile']) || empty($j['content'])) {
            $this->_return_json(array('status' => -1, 'msg' => "Invalid data!"));
        }
        $mobiles = [];
        if(is_array($j['mobile'])) {
            $mobiles = $j['mobile'];
        } else {
            $mobiles = [
                $j['mobile']
            ];
        }

        $site_id = isset($j['site']) ? intval($j['site']) : C('site.dachu');
        if($site_id  == C('site.dachu')) {
            $res = $this->smsapi_dachu->send("captcha", $mobiles, $j['content']);
        } else if($site_id == C('site.daguo')) {
            $res = $this->smsapi_daguo->send("captcha", $mobiles, $j['content']);
        } else {
            $res = $this->smsapi_dachu->send("captcha", $mobiles, $j['content']);
        }
        //$res = "1,1201410242024";
        $res_arr = explode(",", $res);
        //if(count($res_arr) > 1 && $res_arr[0] == 1) {
        //TODO 不成功时也会存储sms_log
        if(TRUE) {
            if(is_array($j['mobile'])) {
                $mobiles = $j['mobile'];
            } else {
                $mobiles = explode(",", $j['mobile']);
            }
            $content = $j['content'];
            $datas = array();
            foreach($mobiles as $mobile) {
                $mobile = trim($mobile);
                $datas[] = array(
                    'type_id'      => $this->type['captcha'],
                    'mobile'       => $mobile,
                    'content'      => $content,
                    'status'       => 1,
                    'created_time' => $this->input->server("REQUEST_TIME"),
                    'updated_time' => $this->input->server("REQUEST_TIME"),
                );
            }
            $log = $this->MSms_log->add_batch($datas);
            $this->_return_json(array('status' => 0, 'msg' => "success", 'sms_status' => $res_arr[0]));
        }else{
            $this->_return_json(array('status' => 0, 'sms_status' => $res, 'msg' => "Sms send failed!"));
        }
    }


    /**
     * 统计短信任务发送状态
     * @param job_ids  Array 任务ID
     * @return list
     * @author fengzongbao@dachuwang.com
     */
    public function stats()
    {
        if(empty($_POST['job_ids'])) {
            $this->_return_json(array(
                'status' => -1,
                'msg'    => 'empty job_ids'
            ));
        }


        $job_ids = $_POST['job_ids'];
        $result = [];
        foreach($job_ids as $job_id) {
            $result[$job_id]['send'] =  $this->MSms_log->count(array(
                'send_response' => 0,
                'job_id'        => $job_id
            ));

            // 查询状态
            $send_logs = $this->MSms_log->get(array(
                'job_id' => $job_id
            ));

            foreach($send_logs as $send_log) {
                if($send_log['send_response'] == NULL) {
                    $result[$job_id]['state'] = 'unpush';
                } elseif($send_log['send_response'] == 0) {
                    $result[$job_id]['state'] = 'success';
                } else {
                    $result[$job_id]['state'] = 'pushing';
                }
            }

        }

        $this->_return_json(array(
            'status' => 0,
            'list'   => $result
        ));
    }
}
