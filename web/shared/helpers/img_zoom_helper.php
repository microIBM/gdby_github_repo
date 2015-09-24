<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('img_zoom')) {
    function img_zoom($data, $zoom_str = "") {
        if(is_array($data)) {
            foreach($data as &$v) {
                $picture = $v['pic_url'];
                $last_pos = strrpos($picture, '.');
                $pre_str = substr($picture, 0, $last_pos);
                $last_str = substr($picture, $last_pos);
                $v['pic_url'] = $pre_str . $zoom_str . $last_str;
            }
            unset($v);
            return $data;
        }
    }
}
