<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 
/**
 * app 短信验证
 * @author wuzhenyu@ymt360.com 14-27
 * @version 1.0.0
 */
class Phone {

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->library(array('crawl'));
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 发送短信校验码
     */
    public function send_captcha($mobile, $vcode) {
        $res = array();
        $data = array(
            'mobile'  => array($mobile),
            'content' => '您好，您的验证码是:' . $vcode . '，校验码在30分钟内有效，谢谢您的使用。',
        );
        $soa_j = $this->CI->crawl->soarpc('/dachu_sms/send_captcha/', json_encode($data));
        if($soa_j['status'] == 0) {
            $res = array('status' => 0);
        } else {
            $res = array('status' => -1);
        }
        return $res;
    }

    /**
     * @param mobile, amount
     */
    public function send_sms($mobile, $content) {
        $res = array(
            'status' => 0,
            'msg' => ''
        );
        $data = array(
            'mobile'  => array($mobile),
            'content' => $content,
        );
        $soa_j = $this->CI->crawl->soarpc('/dachu_sms/send_captcha/', json_encode($data));
        $res['sms_res'] = $soa_j;
        return $res;
    }
}
