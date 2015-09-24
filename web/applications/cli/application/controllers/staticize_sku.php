<?php if ( ! defined("BASEPATH")) exit("No direct script access allowed");

/**
 * 静态化SKU脚本
 * @author: zhangxiao@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-7-22
 */
class staticize_sku extends MY_Controller {

    const DAY_OPERATION       = 1;
    const WEEK_OPERATION      = 2;
    const MONTH_OPERATION     = 3;

    private $run_stime;
    private $run_etime;
    private $error_msg = array();
    private $success_msg = array();

    private $category_top = array();
    private $warehouses = array();
    private $cities = array();

    public function __construct() {
        parent::__construct();
        $this->load->library(
            array(
                "staticize",
                "email"
            )
        );
        $this->cities = C("staticize_open_cities");
        $this->_get_category_top();
        $this->_get_warehouses();
        $this->staticize->set_data_req_url("statistics_bi/get_category_sales_info");
        $this->staticize->set_data_send_url("insert_data/insert");
    }

    /**
     * 按天静态化数据
     * @param $stime_str 和 $etime_str 不传默认跑今日数据
     * @param $stime_str 和 $etime_str 都传为这个时间段内的数据，即可全量跑数据或补某段时间内的数据
     * @author zhangxiao@dachuwang.com
     */
    public function by_day($stime_str = "now", $etime_str = "") {
        //大于等于0点并且小于6点的请求，以前一天的数据开始跑，否则以本天为开始跑数据
        $start_timestamp = strtotime($stime_str);
        $zero_clock_timestamp = strtotime(date('Y-m-d', strtotime("now")));
        $six_clock_timestamp = strtotime(date("Y-m-d", strtotime("now")).' 06:00:00');
        if($start_timestamp >= $zero_clock_timestamp && $start_timestamp <= $six_clock_timestamp) {
            $stime_str = date('Y-m-d', strtotime('-1 day', $start_timestamp));
        }

        $this->_set_stime_etime($stime_str, $etime_str);
        $this->staticize->set_table_name("statics_sku_day");
        $this->_do_staticize(self::DAY_OPERATION);
    }

    /**
     * 按周静态化数据
     * @param $stime_str 和 $etime_str 不传默认跑本周数据
     * @param $stime_str 和 $etime_str 都传为这个时间段内的数据，即可全量跑数据或补某段时间内的数据
     * @author zhangxiao@dachuwang.com
     */
    public function by_week($stime_str = "now", $etime_str = "") {
        //如果是本周的第一天，并且是0点到6点的请求，则跑前一个周的数据。否则跑当周数据
        $start_timestamp = strtotime($stime_str);
        $zero_clock_date = date('Y-m-d', $this->_get_week_time(strtotime("now"))['stime']);
        $zero_clock_timestamp = strtotime($zero_clock_date);
        $six_clock_timestamp = strtotime($zero_clock_date.' 06:00:00');
        if($start_timestamp >= $zero_clock_timestamp && $start_timestamp <= $six_clock_timestamp) {
            $stime_str = date('Y-m-d', strtotime('-7 days', $zero_clock_timestamp));
        }

        $this->_set_stime_etime($stime_str, $etime_str);
        $this->staticize->set_table_name("statics_sku_week");
        $this->_do_staticize(self::WEEK_OPERATION);
    }

    /**
     * 按月静态化数据
     * @param $stime_str 和 $etime_str 不传默认跑本月数据
     * @param $stime_str 和 $etime_str 都传跑这个时间段内的数据，即可全量跑数据或补某段时间内的数据
     * @author zhangxiao@dachuwang.com
     */
    public function by_month($stime_str = "now", $etime_str = "") {
        //如果是本月的第一天，并且是0点到6点的请求，则跑前一个月的数据。否则跑当月数据
        $start_timestamp = strtotime($stime_str);
        $zero_clock_timestamp = strtotime(date('Y-m', strtotime("now")).'-01');
        $six_clock_timestamp = strtotime(date('Y-m', strtotime("now")).'-01 06:00:00');
        if($start_timestamp >= $zero_clock_timestamp && $start_timestamp <= $six_clock_timestamp) {
            $stime_str = date('Y-m', strtotime('-1 month', $start_timestamp));
        }

        $this->_set_stime_etime($stime_str, $etime_str);
        $this->staticize->set_table_name("statics_sku_month");
        $this->_do_staticize(self::MONTH_OPERATION);
    }

    /**
     * 获取某个时间所在周的周一的时间戳和周日的时间戳
     * @author zhangxiao@dachuwang.com
     */
    private function _get_week_time($stime_stamp) {

        $day_week_num = date('w', $stime_stamp);
        if($day_week_num == 0) {
            $day_diff_monday = $day_week_num + 6;
        } else {
            $day_diff_monday = $day_week_num - 1;
        }
        $week_stime = strtotime("-".$day_diff_monday." day", $stime_stamp);
        $week_etime = strtotime("+6 days" ,$week_stime);
        return array(
                'stime' => $week_stime,
                'etime' => $week_etime
        );
    }

    /**
     * 设置数据的开始时间点和结束时间点
     * @author zhangxiao@dachuwang.com
     */
    private function _set_stime_etime($stime_str, $etime_str) {
        $this->staticize->set_stime(strtotime($stime_str));
        if($etime_str) {
            $this->staticize->set_etime(strtotime($etime_str));
        } else {
            $this->staticize->set_etime(strtotime($stime_str));
        }
    }

    /**
     * 获取数据和写入数据操作
     * @author zhangxiao@dachuwang.com
     */
    private function _do_staticize($operation) {

        switch ($operation) {
            case self::DAY_OPERATION :
                $function = "by_day";
                break;
            case self::WEEK_OPERATION :
                $function = "by_week";
                break;
            case self::MONTH_OPERATION :
                $function = "by_month";
                break;
        }
        $this->run_stime = date("Y/m/d H:m:s", $this->input->server('REQUEST_TIME'));
        foreach ($this->category_top as $category_id) {
            foreach ($this->cities as $city) {
                foreach ($this->warehouses as $warehouse) {
                    if($warehouse["location_id"] == $city["code"]) {
                        $this->staticize->set_request_params(array(
                            "category_id"  => $category_id,
                            "city_id"      => $city["code"],
                            "warehouse_id" => $warehouse["warehouse_id"]
                        ));
                        $return_data = $this->staticize->$function();
                        if($return_data["status"] !== 0) {
                            array_push($this->error_msg, $return_data["msg"]);
                            //跳出最外层循环
                            //break 3;
                        } else {
                            array_push($this->success_msg, $return_data);
                        }
                    }
                }
            }
        }
        $this->run_etime = date("Y/m/d H:m:s", strtotime("now"));

        $this->_send_email();
    }

    private function _get_error_info() {
        $error_msg = $this->error_msg;
        $msg  = "SKU统计分析脚本信息<br><br>";
        $msg .= "脚本执行开始时间: ".$this->run_stime."<br>";
        $msg .= "脚本执行结束时间: ".$this->run_etime."<br>";
        $msg .= "脚本运行状态: 失败<br>";
        $msg .= "错误信息数: ".count($error_msg)."<br><br>";
        $count = 0;

        foreach ($error_msg as $value) {
            $msg .= "错误信息 ".$count++." :<br>";
            foreach ($value as $error) {
                $msg .= "方法名: ".$error["method"]."<br>";
                $msg .= "操作: ".$error["operation"]."<br>";
                $msg .= "开始时间: ".date("Y-m-d", $error["stime"])."<br>";
                $msg .= "结束时间: ".date("Y-m-d", $error["etime"])."<br>";
                $msg .= "品类ID: ".$error["req_params"]["category_id"]."<br>";
                $msg .= "城市ID: ".$error["req_params"]["city_id"]."<br>";
                $msg .= "仓库ID: ".$error["req_params"]["warehouse_id"]."<br><br>";
            }
        }
        return $msg;
    }

    private function _get_sucess_info() {
        $success_msg = $this->success_msg;
        $msg  = "SKU统计分析脚本信息<br><br>";
        $msg .= "脚本执行开始时间: ".$this->run_stime."<br>";
        $msg .= "脚本执行结束时间: ".$this->run_etime."<br>";
        $msg .= "脚本运行状态: 成功<br>";
        $msg .= "成功信息数: ".count($success_msg)."<br><br>";
        $count = 0;
    
        foreach ($success_msg as $value) {
            $msg .= "成功信息 ".$count++." :<br>";
            $msg .= "跑表类型: ".$value["params"]['operation']."<br>";
            $msg .= "操作类型: ".$value["type"]."<br>";
            $msg .= "插入数目: ".$value["insert_count"]."<br>";
            $msg .= "更新数目: ".$value["update_count"]."<br>";
            $msg .= "开始时间: ".date("Y-m-d", $value["params"]["stime"])."<br>";
            $msg .= "结束时间: ".date("Y-m-d", $value["params"]["etime"])."<br>";
            $msg .= "品类ID: ".$value["params"]["category_id"]."<br>";
            $msg .= "城市ID: ".$value["params"]["city_id"]."<br>";
            $msg .= "仓库ID: ".$value["params"]["warehouse_id"]."<br><br>";
        }
        return $msg;
    }

    private function _send_email() {
        if($this->error_msg) {
            $msg = $this->_get_error_info();
        } else {
            $msg = $this->_get_sucess_info();
        }
        $this->email->from(C('email_group.bi.sku_statics.from'), C('email_group.bi.sku_statics.name'));
        $this->email->to(implode(",", C('email_group.bi.sku_statics.to')));
        $this->email->subject(C('email_group.bi.sku_statics.subject'));
        $this->email->message($msg);
        $this->email->send();
        echo $this->email->print_debugger();
    }

    private function _get_category_top() {
        $data = $this->cli_query("statistics_bi/get_category_child");
        if($data["status"] === 0) {
            $this->category_top = array_column($data["data"], "category_id");
        }
    }

    private function _get_warehouses() {
        $data = $this->cli_query("statistics_bi/get_warehouse_by_location", array(
            "location_id" => 0
        ));
        if($data["status"] === 0) {
            $this->warehouses = $data["data"];
        }
    }

}

/* End of file staticize_sku.php */
/* Location: ./application/controllers/staticize_sku.php */