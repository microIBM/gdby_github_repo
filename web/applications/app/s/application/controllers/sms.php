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
                'Smsapi_daguo'
            )
        );
        $this->load->model(array('MSms_log'));
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

        $password   = C('sms_new.normal.password');
        $cdkey      = C('sms_new.normal.cdkey');
        $post_url   = C('sms_new.normal.post_url');
        $dachu_sign = C('sms_new.normal.dachu_sign');
        $daguo_sign = C('sms_new.normal.daguo_sign');

        //营销
        if(!empty($_POST['sms_type']) && $_POST['sms_type'] == C('sms_new.marketing.code')) {
            $password   = C('sms_new.marketing.password');
            $cdkey      = C('sms_new.marketing.cdkey');
            $post_url   = C('sms_new.marketing.post_url');
            $dachu_sign = C('sms_new.marketing.dachu_sign');
            $daguo_sign = C('sms_new.marketing.daguo_sign');
        }

        //合法
        $this->load->library(
            array(
                'Dachu_request'
            )
        );

        $mobile = $_POST['mobile'];
        $corp_sign = isset($_POST['site']) && $_POST['site'] == C('site.daguo') ? $daguo_sign : $dachu_sign;
        $addserial = isset($_POST['site']) && $_POST['site'] == C('site.daguo') ? C('site.daguo') : C('site.dachu');
        $content = $corp_sign . $_POST['content'];

        //群发
        if(is_array($mobile)) {
            //群发start
            $offset = 0;
            $tmp_mobile_arr = array_slice($mobile, 0, 180);
            while(!empty($tmp_mobile_arr)) {
                $post_mobile = implode(',', $mobile);
                $data = array(
                    'cdkey'     => $cdkey,
                    'password'  => $password,
                    'phone'     => $post_mobile,
                    'message'   => $content,
                    'addserial' => $addserial,
                );

                $response = $this->dachu_request->get($post_url, $data);
                $response_arr = json_decode(json_encode(simplexml_load_string($response['res'])), TRUE);

                //保存在数据库里的响应内容，如果有错误码，存错误码，出现其它情况直接存错误信息
                $response_to_save = '';
                if($response_arr && isset($response_arr['error'])) {
                    $response_to_save = $response_arr['error'];
                } else {
                    $response_to_save = $response['res'];
                }

                foreach($mobile as $item) {
                    $logs[] = array(
                        'type_id'       => $this->type['notice'],
                        'mobile'        => $item,
                        'content'       => $content,
                        'send_response' => $response_to_save,
                        'status'        => 1,
                        'created_time'  => $this->input->server("REQUEST_TIME"),
                        'updated_time'  => $this->input->server("REQUEST_TIME"),
                    );
                }

                //批量插入短信发送log
                $res = $this->MSms_log->create_batch($logs);
                $offset += 180;
                $tmp_mobile_arr = array_slice($mobile, $offset, 180);
            }

            //群发end

        } else {
            //单条发
            $data = array(
                'cdkey'     => $cdkey,
                'password'  => $password,
                'phone'     => $mobile,
                'message'   => $content,
                'addserial' => $addserial,
            );
            $response = $this->dachu_request->post($post_url, $data);
            $response_arr = json_decode(json_encode(simplexml_load_string($response['res'])), TRUE);

            $log = array(
                'type_id'       => $this->type['notice'],
                'mobile'        => $mobile,
                'content'       => $content,
                'send_response' => $response['res'],
                'status'        => 1,
                'created_time'  => $this->input->server("REQUEST_TIME"),
                'updated_time'  => $this->input->server("REQUEST_TIME"),
            );

            if($response_arr && isset($response_arr['error'])) {
                $log['send_response'] = $response_arr['error'];
            } else {
                $log['send_response'] = $response['res'];
            }

            //插入发送记录
            $this->MSms_log->create($log);
        }

        //亿美响应正常
        if($response_arr && isset($response_arr['error']) && $response_arr['error'] == 0) {
            $this->_return_json(
                array(
                    'status' => 0,
                    'msg'    => 'send sms success return error code ' . $response_arr['error'] . ' and msg ' . implode(',', array_values($response_arr['message']))
                )
            );
        } else {
        //亿美响应异常
            $this->_return_json(
                array(
                    'status' => -1,
                    'msg'    => 'yimei service return error code ' . $response_arr['error'] . ' and msg ' . implode(',', array_values($response_arr['message']))
                )
            );
        }

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
}
