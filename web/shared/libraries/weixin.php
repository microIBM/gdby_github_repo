<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 微信
 * @author: changshaoshuai@dachuwang.com
 * @since : 2015-06-26
 */
class Weixin extends MY_Controller {
    use MemAuto;

    public function __construct() {
        parent::__construct();
    }

    /**
     * 获取微信令牌
     * @auth changshaoshaui@dachuwang.com
     * @since 2015-06-25
     */
    public function wx_get_token() {
        $url = C('wx.token_url');
        $param = array(
            'grant_type' => C('wx.grant_type'),
            'appid'      => C('wx.chu.appid'),
            'secret'     => C('wx.chu.secret')
        );
        $url .= http_build_query($param);
        $res = json_decode(file_get_contents($url));
        $token  = $res->access_token;
        return $token;
    }

    /**
     * 获取jsapi的ticket
     * @author changshaoshuai@dachuwang.com
     * @since 2015-06-25
     */
    public function wx_get_jsapi_ticket() {
        $token = $this->wx_get_token__Cache7000();
        $url = C('wx.ticket_url');
        $param = array(
            'access_token' => $token,
            'type'         => C('wx.ticket_type')
        );
        $url .= http_build_query($param);
        $res = json_decode(file_get_contents($url));
        $ticket = $res->ticket;
        return $ticket;
    }

    /**
     * 产生随机数
     * @author changshaoshuai@dachuwang.com
     * @since 2015-06-27
     */
    private function createNonceStr($length = 10) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 返回签名等
     * @author changshaoshuai@dachuwang.com
     * @since 2015-06-25
     */
    public function wx_sign() {
        $return = array(
            'status' => C('status.req.failed'),
            'msg'    => '签名失败！'
        );

        $ticket = $this->wx_get_jsapi_ticket__Cache7000();
        $time =  $this->input->server('REQUEST_TIME');
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $noncestr = $this->createNonceStr();
        $data = "jsapi_ticket=$ticket&noncestr=$noncestr&timestamp=$time&url=$url";
        $sign = sha1($data);
        $return = array(
            'status'    => C('status.req.success'),
            'msg'       => '签名成功!',
            'data'      => array(
                'appid' => C('wx.chu.appid'),
                'sign'  => $sign,
                'timestamp' => $time,
                'noncestr'  => $noncestr,
                'url'       => $url
            )
        );
        return $return;
    }

    /**
     * 设置session值
     * @author changshaoshuai@dachuwang.com
     * @since 2015-06-27
     */
    private function set_userdata($item, $val) {
        //session_start();
        $_SESSION[$item] = $val; 
    }

    /**
     * 获取session值
     * @author changshaoshuai@dachuwang.com
     * @since 2015-06-27
     */
    private function userdata($item) {
        //session_start();
        $return = isset($_SESSION[$item]) ? $_SESSION[$item] : FALSE;
        return $return;
    }

}
