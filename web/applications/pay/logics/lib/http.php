<?php
/**
 * HTTP请求类
 */
class Http {
    /**
     * 发起一个HTTP/HTTPS的请求
     * 
     * @param $url 接口的URL            
     * @param $params 接口参数 array('content'=>'test', 'format'=>'json');
     * @param $method 请求类型 GET|POST
     * @param $multi 图片信息            
     * @param $extheaders 扩展的包头信息            
     * @return string
     */
    
    public static function request ($url, $params = array(), $method = 'GET',$cookies=array(), $multi = false, $extheaders = array()) {
        if (! function_exists('curl_init')){
            exit('Need to open the curl extension');
        }
        $method = strtoupper($method);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array) $extheaders;
        switch ($method) {
            case 'POST' :
                curl_setopt($ci, CURLOPT_POST, TRUE);
                if (! empty($params)) {
                    if ($multi) {
                        foreach ( $multi as $key => $file ) {
                            $params[$key] = '@' . $file;
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    } else {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                break;
            case 'DELETE' :
            case 'GET' :
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (! empty($params)) {
                    $url = $url . (strpos($url, '?') ? '&' : '?') . (is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }
        curl_setopt($ci, CURLINFO_HEADER_OUT, TRUE);
        curl_setopt($ci, CURLOPT_URL, $url);
        
        if ($headers) {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        }
        if ($cookies) {
            foreach ($cookies as $key => $value){
                $cookie = $key.'='.urlencode($value).'; ';//这里一定要做urlencode，否则会出错
            }
            curl_setopt($ci, CURLOPT_COOKIE, trim($cookie,'; '));
        }
        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
    }
}