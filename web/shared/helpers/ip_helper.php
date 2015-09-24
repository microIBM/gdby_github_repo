<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if(!function_exists("ip2info")) {

    function ip2info($ip) {
        return json_decode(file_get_contents(C('config.location_url') . $ip), TRUE);
    }

}
