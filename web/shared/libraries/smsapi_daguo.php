<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 发送短信的公共接口
 * @author : caiyilong@ymt360.com
 * @version : 1.0.0
 * @since : 2014-10-21
 */
class Smsapi_daguo {

    private $_timeout = 3; // 超时时间

    /**
     * 构造函数
     */
    function __construct() {
        $this->CI = &get_instance();
        //$this->CI->load->model("MSmsInfo");
    }

    /**
     * @description 高优秀级验证码
     */
    public function send($type, $mobile, $content, $dstime = "", $xh = "") {
        if(empty($mobile) || empty($content)) {
            return FALSE;
        }
        $this->api = C("sms.daguo.normal");
        $type = strtoupper($type);
        $product_id = !empty($this->api[$type]) ? $this->api[$type] : $this->api["NOTICE"];
        $data = array(
            'username' => $this->api['USERNAME'],
            'password' => md5($this->api['PASSWORD']),
        );
        $data['mobile'] = implode(",", $mobile);
        if(is_array($content)) {
            $data['content'] = implode("※", $content);
        }else{
            $data['content'] = $content;
        }
        $data['productid'] = $product_id;
        if(!empty($dstime)) {
            $data['dstime'] = $dstime;
        }
        if(count($mobile) == 1) {
            $res = $this->_get_page($this->api['SMS_SINGLE_SERVICE'], $data);
        }else{
            $res = $this->_get_page($this->api['SMS_MULTI_SERVICE'], $data);
        }
        return $res;
    }

    /**
     * @description 低优先级营销短信
     */
    public function send_marketing($type, $mobile, $content, $dstime = "", $xh = "") {
        if(empty($mobile) || empty($content)) {
            return FALSE;
        }
        $this->api = C('sms.daguo.marketing');
        $type = strtoupper($type);
        $product_id = !empty($this->api[$type]) ? $this->api[$type] : $this->api["NOTICE"];
        $data = array(
            'username' => $this->api['USERNAME'],
            'password' => md5($this->api['PASSWORD']),
        );
        $data['mobile'] = implode(",", $mobile);
        if(is_array($content)) {
            $data['content'] = implode("※", $content);
        }else{
            $data['content'] = $content;
        }
        $data['productid'] = $product_id;
        if(!empty($dstime)) {
            $data['dstime'] = $dstime;
        }
        if(count($mobile) == 1) {
            $res = $this->_get_page($this->api['SMS_SINGLE_SERVICE'], $data);
        }else{
            $res = $this->_get_page($this->api['SMS_MULTI_SERVICE'], $data);
        }
        return $res;
    }


    /**
     * 请求数据
     * @param string $url 请求地址
     * @param array $payload 传入参数
     */
    private function _get_page($url, $payload = FALSE) {
        $ch = $this->build_ch($url, $payload);
        $page = curl_exec($ch);
        if(curl_errno($ch)) {
            return "curl request error";
        }
        curl_close($ch);
        return $page;
    }

    /**
     * 构建请求参数
     * @param string $url 请求地址
     * @param array $payload 传入参数
     */
    private function build_ch($url, $payload = FALSE){
        $url = $url . '?' . http_build_query($payload);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->_timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        return $ch;
    }
}
