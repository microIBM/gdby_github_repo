<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 定时数据统计脚本
 * @author: zhangxiao@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-6-24
 */
class staticize extends MY_Controller {

    //数据总统计表：得到按天数据接口地址
    const STATICS_GET_DAY_DATA_URL  = '/order_bi/statics_by_day';
    //数据总统计表：得到时间段内数据接口地址
    const STATICS_GET_PERIOD_DATA_URL = '/order_bi/statics_period';

    //数据总统计表：更新按天数据到数据库的接口地址
    const STATICS_SEND_DAY_DATA_URL = '/staticize/staticize_data';
    //数据总统计表：更新历史数据到数据库的接口地址
    const STATICS_SEND_HISTORY_DATA_URL = '/staticize/staticize_data_history';
    //数据总统计表：更新月总计数据到数据库的接口地址
    const STATICS_SEND_MONTH_DATA_URL = '/staticize/staticize_data_month';

    /*标识所进行的不同数据操作类型
     * 1 => 每天数据
     * 2 => 每天全量数据
     * 3 => 每月数据
     * 4 => 每月全量数据
     * 5 => 历史数据
     */
    const STATICS_DAY = 1;
    const STATICS_DAY_ALL = 2;
    const STATICS_MONTH = 3;
    const STATICS_MONTH_ALL = 4;
    const STATICS_HISTORY = 5;

    //数据操作类型
    private $_data_operation;

    //所需数据的开始时间
    private $_stime;
    //所需数据的结束时间
    private $_etime;
    //所需数据的日期(Y-m-d)
    private $_date;
    //所需数据的月份(Y-m)
    private $_date_month;

    //城市
    private $_open_cities;
    //客户类型
    private $_customer_types;

    public function __construct() {
        parent::__construct();
        $this->_open_cities    = C('staticize_open_cities');
        $this->_customer_types = C('staticize_customer_types');
    }

    /**
     * 数据总统计表：按天跑数据, 全量跑数据
     * 起始时间：2015年3月1日 | 结束时间：当前时间
     * @author zhangxiao@dachuwang.com
     */
    public function set_statics_all() {
        $run_start_time = strtotime("now");
        $this->_data_operation = self::STATICS_DAY_ALL;
        $history_start   = strtotime('2015-03-01');
        $histroy_end     = strtotime("now");
        for($i = $history_start; $i < $histroy_end; $i += 86400) {
          $this->_stime = $i;
          $this->_etime = $this->_stime + 86400;
          $this->_date  = date("Y-m-d", $this->_stime);
          $this->_send_statics();
        }
        $run_end_time = strtotime("now");
        echo 'RUNTIME: '.($run_end_time - $run_start_time)." second";
    }

    /**
     * 数据总统计表：按天跑数据, 默认跑今日数据
     * 一天内重复跑则更新数据，一天内首次跑则插入数据
     * @author zhangxiao@dachuwang.com
     */
    public function set_statics_by_day($which_day = "now") {
        $run_start_time = strtotime("now");
        $this->_data_operation = self::STATICS_DAY;
        $start_time = strtotime($which_day);
        $zero_clock_timestamp = strtotime(date('Y-m-d', $start_time));
        $four_clock_timestamp = strtotime(date('Y-m-d', $start_time).' 03:59:59');

        //大于等于0点并且小于4点的请求，跑前一天的数据，否则跑本天的数据
        if($start_time >= $zero_clock_timestamp && $start_time <= $four_clock_timestamp) {
            $this->_stime = strtotime(date("Y-m-d", $start_time - 86400));
            $this->_etime = $this->_stime + 86400;
            $this->_date  = date("Y-m-d", $start_time - 86400);
        } else {
            $this->_stime = strtotime(date("Y-m-d", $start_time));
            $this->_etime = $this->_stime + 86400;
            $this->_date  = date("Y-m-d", $start_time);
        }

        $this->_send_statics();
        $run_end_time = strtotime("now");
        echo 'RUNTIME: '.($run_end_time - $run_start_time)." second";
    }

    /**
     * 数据总统计表-历史统计
     * @author zhangxiao@dachuwang.com
     */
    public function set_statics_history() {
        $run_start_time = strtotime("now");
        $this->_data_operation = self::STATICS_HISTORY;
        $this->_stime   = strtotime('2015-03');
        $this->_etime   = strtotime("now");
        $this->_send_statics();
        $run_end_time = strtotime("now");
        echo 'RUNTIME: '.($run_end_time - $run_start_time)." second";
    }

    /**
     * 数据总统计表：按月跑数据, 全量跑数据
     * 起始时间：2015年3月 | 结束时间：当前月
     * @author zhangxiao@dachuwang.com
     */
    public function set_statics_month_all() {
        $run_start_time = strtotime("now");
        $this->_data_operation = self::STATICS_MONTH_ALL;
        $month_start = strtotime('2015-03');
        $month_end   = strtotime(date("Y-m", strtotime("now")));
        while($month_start <= $month_end) {
            $this->_stime = $month_start;
            $this->_etime = strtotime("+1 month", $month_start);
            $this->_date  = date("Y-m-d", $this->_stime);
            $this->_send_statics();
            $month_start = $this->_etime;
        }
        $run_end_time = strtotime("now");
        echo 'RUNTIME: '.($run_end_time - $run_start_time)." second";
    }

    /**
     * 数据总统计表-月统计
     * @author zhangxiao@dachuwang.com
     */
    public function set_statics_month($which_month = "now") {
        $run_start_time = strtotime("now");
        $this->_data_operation = self::STATICS_MONTH;
        $start_time = strtotime($which_month);
        $zero_clock_timestamp = strtotime(date('Y-m', $start_time).'-01');
        $four_clock_timestamp = strtotime(date('Y-m', $start_time).'-01 03:59:59');
        
        //如果是本月的第一天，并且是0点到4点的请求，则跑前一个月的数据。否则跑当月数据
        if($start_time >= $zero_clock_timestamp && $start_time <= $four_clock_timestamp) {
        	$this->_stime   = strtotime("-1 month", strtotime(date("Y-m", $start_time)));
        	$this->_etime   = strtotime(date("Y-m", $start_time));
        }else {
        	$this->_stime   = strtotime(date("Y-m", $start_time));
        	$this->_etime   = strtotime(date("Y-m", strtotime("+1 month", $start_time)));
        }
        
        $this->_send_statics();
        $run_end_time = strtotime("now");
        echo 'RUNTIME: '.($run_end_time - $run_start_time)." second";
    }

    /**
     * 数据总统计表：得到数据
     * @author zhangxiao@dachuwang.com
     */
    private function  _get_statics($city_id, $cus_type) {
        $cityname_by_code    = array_column($this->_open_cities, 'name', 'code');
        $custypename_by_code = array_column($this->_customer_types, 'name', 'code');
        $params = array(
            'city_id' => $city_id,
            'customer_type' => $cus_type,
            'stime'   => $this->_stime,
            'etime'   => $this->_etime
        );
        $data_origin = $this->_request($this->_get_url_by_operation($this->_data_operation)['get_url'], $params, 'POST');
        $data        = json_decode($data_origin, TRUE);

        if($data['status'] === 0) {
            echo "数据>>>".$cityname_by_code[$city_id]."-".$custypename_by_code[$cus_type].">>>接收完毕\n";
            $assemble_data = array();
            $assemble_data['city_id']               = $city_id;
            $assemble_data['customer_type']         = $cus_type;
            return array_merge($assemble_data, $this->_assemble_data($data));
        } else {
            echo "数据>>>".$cityname_by_code[$city_id]."-".$custypename_by_code[$cus_type].">>>接收失败\n";
            return null;
        }
    }
    
    /**
     * 组装得到的数据
     * @author zhangxiao@dachuwang.com
     */
    private function _assemble_data($data) {
        $date = $this->_date;
        $assemble_data = array();
        if($this->_data_operation == self::STATICS_DAY || $this->_data_operation == self::STATICS_DAY_ALL) {
            $assemble_data['date_time']             = $this->_stime;
            $assemble_data['order_count']           = isset($data['data'][$date]['valid_order_cnt']) ? $data['data'][$date]['valid_order_cnt'] :0;
            $assemble_data['order_amount']          = isset($data['data'][$date]['valid_order_amount']) ? $data['data'][$date]['valid_order_amount'] :0;
            $assemble_data['potential_cus_count']   = isset($data['data'][$date]['potential_cus_cnt']) ? $data['data'][$date]['potential_cus_cnt'] : 0;
            $assemble_data['register_cus_count']    = isset($data['data'][$date]['resign_cus_cnt']) ? $data['data'][$date]['resign_cus_cnt'] : 0;
            $assemble_data['ordered_cus_count']     = isset($data['data'][$date]['order_cus_cnt']) ? $data['data'][$date]['order_cus_cnt'] : 0;
            $assemble_data['first_order_cus_count'] = isset($data['data'][$date]['first_ordered_count']) ? $data['data'][$date]['first_ordered_count'] : 0;
            $assemble_data['again_order_cus_count'] = isset($data['data'][$date]['again_ordered_count']) ? $data['data'][$date]['again_ordered_count'] : 0;
            $assemble_data['first_order_amount']    = isset($data['data'][$date]['first_amount']) ? $data['data'][$date]['first_amount'] : 0;
            $assemble_data['again_order_amount']    = isset($data['data'][$date]['again_amount']) ? $data['data'][$date]['again_amount'] : 0;
            return $assemble_data;
        } else {
            if ($this->_data_operation != self::STATICS_HISTORY) {
                $assemble_data['date_time']             = $this->_stime;
            }
            $assemble_data['order_count']         = isset($data['data']['period_valid_order_cnt']) ? $data['data']['period_valid_order_cnt'] :0;
            $assemble_data['order_amount']      = isset($data['data']['period_valid_order_amount']) ? $data['data']['period_valid_order_amount'] :0;
            $assemble_data['potential_cus_count']           = isset($data['data']['period_potential_cus']) ? $data['data']['period_potential_cus'] : 0;
            $assemble_data['register_cus_count']              = isset($data['data']['period_resign_cus']) ? $data['data']['period_resign_cus'] : 0;
            $assemble_data['ordered_cus_count'] = isset($data['data']['period_ordered_customers_total']) ? $data['data']['period_ordered_customers_total'] : 0;
            $assemble_data['again_order_cus_count']   = isset($data['data']['period_again_customers_total']) ? $data['data']['period_again_customers_total'] : 0;
            $assemble_data['first_order_cus_count']    = isset($data['data']['period_first_customer_total']) ? $data['data']['period_first_customer_total'] : 0;
            return $assemble_data;
        }
    }

    /**
     * 数据总统计表：发送数据使数据更新至数据库
     * @author zhangxiao@dachuwang.com
     */
    private function _send_statics() {
        $statics = array();
        $success = TRUE;
        echo "\n\n";
        echo "TIME: ".date('Y-m-d', $this->_stime)." - ".date('Y-m-d', $this->_etime)."\n";
        echo "TYPE: ".$this->_get_url_by_operation($this->_data_operation)['name']."\n";
        foreach ($this->_open_cities as $city) {
            foreach ($this->_customer_types as $cus_type) {
                $data = $this->_get_statics($city['code'], $cus_type['code']);
                if(!$data) {
                    $success = FALSE;
                }
                array_push($statics, $data);
            }
        }

        if ($success) {
            $send_data = array(
                'data' => $statics
            );
            $response = $this->_request($this->_get_url_by_operation($this->_data_operation)['send_url'], $send_data, 'POST');
            $response_arr   = json_decode($response, TRUE);
            if($response_arr['status'] === 0) {
                echo "\n";
                echo "》》》".$response_arr['msg']."\n";
            } else {
                echo "\n";
                echo "》》》".$response_arr['msg']."\n";
            }
        } else {
            echo "\n";
            echo "》》》数据获取不完整\n";
            exit;
        }
    }

    /**
     * 根据不同的操作类型返回对应的接口地址
     * @author zhangxiao@dachuwang.com
     */
    private function _get_url_by_operation($operation) {
        switch ($operation) {
            case self::STATICS_DAY :
                return array(
                    'get_url'  => C("service.s").self::STATICS_GET_DAY_DATA_URL,
                    'send_url' => C("service.s").self::STATICS_SEND_DAY_DATA_URL,
                    'name'     => '每日统计-单量'
                );
            case self::STATICS_DAY_ALL :
                return array(
                    'get_url'  => C("service.s").self::STATICS_GET_DAY_DATA_URL,
                    'send_url' => C("service.s").self::STATICS_SEND_DAY_DATA_URL,
                    'name'     => '每日统计-全量'
                );
            case self::STATICS_MONTH :
                return array(
                    'get_url'  => C("service.s").self::STATICS_GET_PERIOD_DATA_URL,
                    'send_url' => C("service.s").self::STATICS_SEND_MONTH_DATA_URL,
                    'name'     => '每月统计-单量'
                );
            case self::STATICS_MONTH_ALL :
                return array(
                    'get_url'  => C("service.s").self::STATICS_GET_PERIOD_DATA_URL,
                    'send_url' => C("service.s").self::STATICS_SEND_MONTH_DATA_URL,
                    'name'     => '每月统计-全量'
                );
            case self::STATICS_HISTORY :
                return array(
                    'get_url'  => C("service.s").self::STATICS_GET_PERIOD_DATA_URL,
                    'send_url' => C("service.s").self::STATICS_SEND_HISTORY_DATA_URL,
                    'name'     => '历史统计'
                );
        }
    }

    /**
     * 发起一个HTTP/HTTPS的请求
     * @param $url 接口的URL
     * @param $params 接口参数 array('content'=>'test', 'format'=>'json');
     * @param $method 请求类型 GET|POST
     * @param $multi 图片信息
     * @param $extheaders 扩展的包头信息
     * @return string
     */
    private function _request ($url, $params = array(), $method = 'GET', $multi = false, $extheaders = array()) {
        if (! function_exists('curl_init')){
            exit('Need to open the curl extension');
        }
        $method = strtoupper($method);
        $ci = curl_init();
        //curl_setopt($ci, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 1000);
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
        // curl_setopt($ci, CURLOPT_COOKIE, $cookie);
        $response = curl_exec($ci);
        curl_close($ci);
        return $response;
    }
}

/* End of file staticize.php */
/* Location: ./application/controllers/staticize.php */