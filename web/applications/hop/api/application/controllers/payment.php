<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 与支付账单相关的服务
 * @author lenjent
 */
class Payment extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('MPay_bills','payment');
        
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }
    
    /**
     * 下载支付对账单
     * @param bill_from 
     * @param bill_type
     * @param bill_stime
     * @param bill_etime
     * @author yuanxiaolin@dachuwang.com
     */
    public function download(){
        
        $bill_from = $this->input->get_post('bill_from');  //支付平台,如：微信支付，支付宝支付。。。
        $bill_type =  $this->input->get_post('bill_type'); //账单类型，如：支付成功，支付失败，退款。。。
        $bill_stime = $this->input->get_post('bill_stime'); //账单查询起始时间
        $bill_etime = $this->input->get_post('bill_etime');//账单查询结束时间
        
        
        $where['pay_type'] = (int) $bill_from;
        $where['pay_status'] = (int) $bill_type;
        $where['created_time >='] = strtotime($bill_stime);
        $where['created_time <='] = strtotime($bill_etime);
        
        $bill_lists = $this->payment->get_lists(array('*'),$where);
        
        if(!empty($bill_lists)){
            $pay_type = C('payment.type');
            $pay_status = C('payment.status');
            
            $file_name = '/tmp/'.date(date('YmdHis')).'.csv';
            @$tempfile = fopen($file_name, 'x+');
            fputcsv($tempfile, array('编号','订单ID','支付方式','支付状态','支付折扣','流水号','订单号','总支付','现金支付','交易时间','','数据包','','用户标识'));
            foreach ($bill_lists as $key => $value){
                
                if ($value['pay_type'] == $pay_type['offline']['code']) {
                    $bill_lists[$key]['pay_type'] = $pay_type['offline']['msg'];
                }else if($value['pay_type'] == $pay_type['weixin']['code']){
                    $bill_lists[$key]['pay_type'] = $pay_type['weixin']['msg'];
                }
                
                if ($value['pay_status'] == $pay_status['success']['code']) {
                    $bill_lists[$key]['pay_status'] = $pay_status['success']['msg'];
                }else if($value['pay_status'] == $pay_status['failed']['code']){
                    $bill_lists[$key]['pay_status'] = $pay_status['failed']['msg'];
                }
                $bill_lists[$key]['total_fee'] /= 100;
                $bill_lists[$key]['cash_fee'] /= 100;
                $bill_lists[$key]['created_time'] = date('Y-m-d H:i:s',$value['created_time']);
                $full_data = json_decode($value['full_data'],true);
                $full_data && $bill_lists[$key]['open_id'] = $full_data['openid'];
                
                fputcsv($tempfile, $bill_lists[$key]);
            }
            
        }
        
        if(!empty($bill_lists) ){
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=".$file_name);
            readfile($file_name);
            fclose($tempfile);
            unlink($file_name);
        }else{
            die('暂无账单数据');
        }
    }
    

}
