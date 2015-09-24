<?php

class Customer extends Common {
    
    /**
     * 获取当前登陆用户信息
     * @throws Exception
     * @return array:
     * @author yuanxiaolin@dachuwang.com
     */
    public static function get_login_customer () {
        $result = Common::DoApi(Config::API_LOGIN_USER, array(), 'POST',$_COOKIE);
        if (empty($result['info'])) {
            $error_info = sprintf('call Customer::get_current_user api error|api_url:%s|api_return:%s', Config::API_LOGIN_USER, json_encode($result));
            throw new Exception(isset($result['msg']) ? $result['msg'] : '获取用户信息出错');
        }
        return $result['info'] ? $result['info'] : array();
    }
}