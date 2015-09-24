<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @author: liaoxianwen@ymt360.com
 * @description 通用过滤api接口吐的字段
 */
if ( ! function_exists('api_filter_fields')) {
    function api_filter_fields(&$filter_data, $required_fields_arr) {
        return $filter_data;
        foreach($filter_data as $key => &$data) {
            if(is_array($data)) {
                foreach($data as $k => &$v) {
                    //if(!in_array($k, $required_fields_arr)) {
                        //unset($data[$k]);
                    //}
                    if(!isset($required_fields_arr[$k])) {
                        unset($data[$k]);
                    }else {
                        if(is_array($required_fields_arr[$k])) {
                            api_filter_fields($v, $required_fields_arr[$k]);
                        }
                    }
                }
            }else {
                if(!isset($required_fields_arr[$key])) {
                    unset($filter_data[$key]);
                }
            }
        }
    }
}
