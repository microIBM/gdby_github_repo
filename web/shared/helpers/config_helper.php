<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('C')) {
    // read config
    function C($config_path) {
        $CI =& get_instance();
        $keys = explode('.', trim($config_path, '.')); 
        $config = array_shift($keys);
        $CI->load->config($config, TRUE);
        $config = $CI->config->item($config);
        while(!is_null($key = array_shift($keys))) {
            if(is_array($config) && array_key_exists($key, $config)) {
                $config = $config[$key];
            } else {
                trigger_error('Config read error : ' . $config_path);
                return FALSE;
            }
        }
        return $config;
    }
}
