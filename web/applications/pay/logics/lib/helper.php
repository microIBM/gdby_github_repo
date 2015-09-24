<?php
/**
 * 公共函数类
 */
class Helpers {
    // 获取客户端IP
    public static function getClientIp () {
        $ip = getenv('HTTP_CLIENT_IP');
        if (! strcasecmp($ip, 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
            if (! strcasecmp($ip, 'unknown')) {
                $ip = getenv('REMOTE_ADDR');
            }
        }
        return $ip;
    }
    
    public static function isInt ($v) {
        if (is_numeric($v)) {
            if ($v == ceil($v)) {
                return true;
            }
        }
        return false;
    }
}