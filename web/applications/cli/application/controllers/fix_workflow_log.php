<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 修复workflow_log表中的订单时间和状态到静态表order_update中
 * Class Fix_workflow_log
 */
class Fix_workflow_log extends MY_Controller{

    private $stime;
    private $etime;

    private $order_status = [];

    public function __construct(){
        parent::__construct();
        $this->load->model(['MWorkflow_log', 'MSuborder', 'Morder_update']);
        /* 只分析订单状态为12356的 */
        $this->order_status = [C('order.status.success.code'), C('order.status.wait_confirm.code'), C('order.status.confirmed.code'), C('order.status.delivering.code'), C('order.status.wait_comment.code')];
        $this->email_group = C('email_push_group');
    }


    /**
     * 修复方法,已日为单位,可以重复跑,可以跑多天
     * @param string $stime 开始时间,不传默认今天
     * @param null $etime 结束时间,不传默认跑今天
     * @return string 结果字符串
     */
    public function by_day($stime = 'now', $etime = null){
        /* 凌晨0-6点跑的话,就跑昨天的 */
        if(($stime == 'now') && (date('H') >= 0) && (date('H') <= 6)){
            $this->stime = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day')));
        }else{
            $this->stime = strtotime(date('Y-m-d 00:00:00', strtotime($stime)));
        }
        /* 结束时间初始化 */
        if(!empty($etime) && (strtotime($etime) > $this->stime)){
            $end_time = strtotime($etime);
        }else{
            $end_time = $this->stime + 86400 - 1;
        }
        /* 时间段循环,以天为单位进行循环 */
        $return_str = ['status' => '失败', 'date' => '', 'insert' => 0, 'update' => 0];
        for(;$this->stime <= $end_time; $this->stime += 86400){
            $this->etime = $this->stime + 86400 - 1;
            /* 根据条件获取所有需要分析的子订单 */
            $workflow_data = $this->MWorkflow_log->get_order_time($this->stime, $this->etime, $this->order_status);
            if(empty($workflow_data)){
                $return_str['status'] = '成功';
                $return_str['date'] = date("Y-m-d", $this->stime);
                continue;
            }
            /* 根据子单获取母单,并组成可以被使用的状态 */
            $child_order_ids_arr = array_unique(array_column($workflow_data, 'obj_id'));
            $order_arr = $this->MSuborder->get_order_ids_by_suborder($child_order_ids_arr);
            $order_arr = array_column($order_arr, NULL, 'suborder_id');

            /* 分析得到可以直接向数据库插入的数组,重复数据自动更新 */
            $i = 0;
            $insert_arr = array();
            $now_time = time();
            foreach($workflow_data AS $val){
                if(isset($order_arr[$val['obj_id']])){
                    $order_id = $order_arr[$val['obj_id']]['order_id'];
                    $insert_arr[$i]['order_id']     = $order_id;
                    $insert_arr[$i]['order_status'] = $val['operate_type'];
                    $insert_arr[$i]['modify_time']  = $val['created_time'];
                    $insert_arr[$i]['create_time']  = $now_time;
                    $i++;
                }
            }

            $insert_res = $this->Morder_update->replace_into($insert_arr);

            $return_str['date'] = date("Y-m-d", $this->stime);
            $return_str['status'] = '失败';
            if(is_array($insert_res)){
                $return_str['status'] = '成功';
                $return_str['insert'] = $insert_res['insert_counts'];
                $return_str['update'] = $insert_res['update_counts'];
            }
        }
        echo $send_str = 'BI修复workflow_log表日报<br>执行状态:' . $return_str['status'] . '<br>处理时间:' . $return_str['date'] . '<br>插入条数:' . $return_str['insert'] . '<br>更新条数:' . $return_str['update'];
        /* 发送处理结果邮件 */
        $this->format_query('email_report/send', array(
            'to'      => $this->email_group['fix_workflow_log']['to'],
            'cc'      => $this->email_group['fix_workflow_log']['cc'],
            'name'    => $this->email_group['fix_workflow_log']['name'],
            'subject' => $this->email_group['fix_workflow_log']['subject'],
            'title'   => ' ',
            'desc'    => [$send_str],
        ));
    }
}