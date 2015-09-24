<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
if(!function_exists('is_ios')) {
    function is_ios() {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if(strpos($agent, 'ipod') || strpos($agent, 'ipad') || strpos($agent, 'iphone')) {
            return TRUE;
        }
        return FALSE;
    }
}
