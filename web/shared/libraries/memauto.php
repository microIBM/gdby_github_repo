<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 侵入式缓存，可通过魔法函数调用
 * @author: caiyilong@ymt360.com
 * @version: 1.0.0
 * @since: 2015-01-08
 */
trait MemAuto {
    public function __call($name, $arguments) {
        $time = NULL;
        $CI =& get_instance();
        $CI->load->library(array('Memcached_thncr'));
        $surffix_cache = substr($name, strrpos($name, '__Cache'));
        $cache_time = intval(ltrim($surffix_cache, '__Cache'));
        $surffix_lock = substr($name, strrpos($name, '__Lock'));
        $lock_time = intval(ltrim($surffix_lock, '__Lock'));
        $is_cache = TRUE;
        if ($this->_check_surffix($surffix_cache, $cache_time)) {
            //do nothing
            $surffix = $surffix_cache;
            $time = $cache_time;
        } else if($this->_check_surffix($surffix_lock, $lock_time)) {
            $surffix = $surffix_lock;
            $is_cache = FALSE;
            $time = $lock_time;
        } else {
            throw new Exception('Called function is not defined. And not in MemAuto valid function.');
        }
        $key = __CLASS__ . ':' . $name . ':' . md5(serialize($arguments));
        if ( ($res = $CI->memcached_thncr->get($key)) !== FALSE) {
            if($is_cache) {
                return $res;
            } else {
                return !$is_cache;
            }
        }

        $res = call_user_func_array(array($this, rtrim($name, $surffix)), $arguments);

        $CI->memcached_thncr->set($key, $res, $time);
        return $res;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 这个暂时也没有找到什么好得方法
     */
    private function _check_surffix($surffix_cache, $time) {
        return $surffix_cache && $time > 0;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 创建解锁
     */
    public function create_unlock($name, $arguments) {
        $key = __CLASS__ . ':' . $name . ':' . md5(serialize($arguments));
        $CI =& get_instance();
        $CI->load->library(array('Memcached_thncr'));

        $CI->memcached_thncr->delete($key);
    }

}
