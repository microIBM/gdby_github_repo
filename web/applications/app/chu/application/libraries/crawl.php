<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * curl抓取类
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: 2014-12-10
 */
class Crawl {

    private $requests = array();

    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->config->load('soa',TRUE);
        $this->soaconfig = $this->CI->config->item('soa');
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 使用已封装好的soa服务
     */
    public function soarpc($resource_url, $payload = FALSE, $debug = FALSE) {
        $url = sprintf("%s%s", $this->soaconfig["SOA_SERVER"], $resource_url);
        for($i = 0; $i < $this->soaconfig["MAX_FETCH_COUNT"]; $i++){
            $j = $this->_get_page($url, $this->soaconfig["FETCH_TIME_OUT"], $payload);
            if($debug){
                var_dump($url,$j);
            }
            if(!$j){
                continue;
            }

            $j = json_decode($j,TRUE);
            if(!$j){
                continue;
            }

            if(!isset($j["status"]) || (int)$j["status"] !== 0){
                continue;
            }
            return $j;
        }

        return FALSE;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取页面信息
     */
    private function _get_page($url,$timeout=1,$payload=FALSE, $header = FALSE) {
        $ch = $this->_build_ch($url,$timeout,$payload, $header);
        $page = curl_exec($ch);
        curl_close($ch);
        return $page;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @description curl 提交信息
     */
    private function _build_ch($url, $timeout = 1, $payload = FALSE, $header = FALSE){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.107");
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

        if($payload !== FALSE) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        if($header !== FALSE) {
            if(is_array($header)) {
                $tmp = array();
                foreach($header as $key => $value){
                    $tmp[] = trim($key) . ':' . trim($value);  
                }
                curl_setopt($ch, CURLOPT_HEADER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $tmp);
            } 
        } else {
            curl_setopt($ch, CURLOPT_HEADER, 0);
        }
        return $ch;
    }
}
