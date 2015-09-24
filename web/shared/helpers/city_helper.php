<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @author: liaoxianwen@ymt360.com
 * @description 判断地理位置是否为直辖市
 */
if(!function_exists('is_direct_city')) {
    function is_direct_city($province_id) {
        $arr_municipality = C('status.municipality');
        if(in_array($province_id, $arr_municipality)) {
            return TRUE;
        }
        return FALSE;
    }
}


