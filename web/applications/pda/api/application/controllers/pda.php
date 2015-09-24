<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 扫码更新订单状态
 * @author yuanxiaolin@dachuwang.com
 *
 */
class Pda extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('DataModel','datamodel');
        $this->load->model("MSuborder",'ordermodel');
        $this->load->model('MWorkflow_log','logmodel');
        $this->load->model('MUser','usermodel');
        $this->load->model('MPick_task','pickmodel');
        $this->load->model('MDistribution','dispatchmodel');
    }
    
    /**
     * 分拣单扫码接口
     * 用法：http://api.pda.dachuwang.com/pda/picking
     * @param $code_number 配送单号 post参数 必须 如：F-201504141157481645
     * @param $operator_uid  操作员user_id 必须
     * @author yuanxiaolin@dachuwang.com
     */
    public function picking(){
    	
        try {
            $params = $this->datamodel->get_params();
            if(!empty($params['code_number'])){
                $pick_task_info = $this->picking_info($return_array = true);
                if(count($pick_task_info) >0){
                    $where['pick_task_id'] = $pick_task_info['id'];
                    $where['status !='] = C('order.status.closed.code');
                    //$picking_status = C('order.status.allocated.code'); //将已分拨状态改为已复核
                    $picking_status = C('order.status.checked.code');
                    
                    $order_result = $this->ordermodel->get_lists(array('id,order_number,status'),$where);
                    $origin_orders = $this->assemble_orders_by_id($order_result);
                    $checked_orders = $this->check_orders_status('picking',$order_result);
                    $order_ids = array_keys($checked_orders);
                    
                    if(!empty($order_ids)){
                    	
                    	//批量更新订单状态
                        $affact_rows = $this->ordermodel->update_batch_orders_status($order_ids,$picking_status);
                        if($affact_rows >0){
                        	
                        	//记录订单更新日志
                            $operator = $this->usermodel->get_one(array('role_id','name','id'),array('id'=>$params['operator_uid']));
                            $operator['ip'] = $this->input->ip_address();
                            foreach ($order_ids as $value){
                                $this->logmodel->record_order($value,$picking_status,$operator,'扫码更新订单－已复核');
                            }
                            
                            //更新分拣任务状态及记录更新日志
                            $conditions['id'] = $pick_task_info['id'];
                            $status = C('pick_task.status.finished');
                            $data['status'] = $status['code'];
                            
                            if($this->pickmodel->update_where($data,$conditions)){
                            	$this->logmodel->record_pick_task($conditions['id'],$data['status'] ,$operator,"扫码更新分拣任务－{$status['msg']}",$status['msg']);
                            }
                            
                            $this->success($affact_rows);
                        }else{
                            $this->failed('分拣任务号无订单匹配');
                        }
                    }else if(!empty($origin_orders)){
                        $this->failed('分拣任务相关订单已分拣');
                    }else{
                        $this->failed('此分拣任务无订单信息');
                    }
                    
                }else {
                    $this->failed('无效的分拣任务单号');
                }
            }else{
                $this->failed('无效的分拣任务单号');
            }
        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
        
    }
    
    /**
     * 配送单扫码接口
     * 用法：http://api.pda.dachuwang.com/pda/dispatch
     * @param $code_number 配送单号 post参数 必须 如：F-201504141157481645
     * @param $operator_uid  操作员user_id 必须
     * @author yuanxiaolin@dachuwang.com
     */
    public function dispatch(){
        try {
            $params = $this->datamodel->get_params();
            if(!empty($params['code_number'])){
                
                $dispatch_info = $this->dispatch_info($return_array = true);
                if (count($dispatch_info) > 0) {
                    $where['dist_id'] = $dispatch_info['id'];
                    $where['status !='] = C('order.status.closed.code');
                    $dispatch_status = C('order.status.delivering.code');
                    $order_result = $this->ordermodel->get_lists(array('id,order_number,status'),$where);
                    $origin_orders = $this->assemble_orders_by_id($order_result);
                    
                    $checked_orders = $this->check_orders_status('dispatch',$order_result);
                    $order_ids = array_keys($checked_orders);
                    
                    if(!empty($order_ids)){
                        $affact_rows = $this->ordermodel->update_batch_orders_status($order_ids,$dispatch_status);
                        if($affact_rows >0){
                            // 更新配送单为已发运状态
                            $this->dispatchmodel->update_info(['status' => C('distribution.status.shipped.code')], ['id' => $dispatch_info['id']]);
                            $operator = $this->usermodel->get_one(array('*'),array('id'=>$params['operator_uid']));
                            $operator['ip'] = $this->input->ip_address();
                            foreach ($order_ids as $value){
                                $this->logmodel->record_order($value,$dispatch_status,$operator,'扫码更新订单－配送中');
                            }
                            $this->success($affact_rows);
                        }else{
                            $this->failed('订单更新失败');
                        }
                    }else if(!empty($origin_orders)){
                        $this->success(count($origin_orders));
                    }else{
                        $this->failed('此配送单无订单信息');
                    }
                }else {
                    $this->failed('无效的配送单号');
                }
            }else{
                $this->failed('无效的配送单号');
            }
        } catch (Exception $e) {
            $this->failed($e->getMessage());
        }
    }
    
    /**
     * 获取分拣单信息
     * @param $code_number :分拣单号，post 参数
     * @param $operator_uid:执行操作user_id，post参数
     * @return Ambigous <multitype:, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    public function picking_info($return_array = false){
    	
        try {
            $pick_task_info = array();
            $params = $this->datamodel->get_params();
            
            if(!empty($params['code_number']) && !empty($params['operator_uid'])){
                $fields = array('id','pick_number','created_time','status','wave_id','sku_count','site_src','line_id');
                $pick_task_info = $this->pickmodel->get_one($fields,array('pick_number'=>$params['code_number']));
                if(!empty($pick_task_info) && !isset($pick_task_info['order_count'])){
                    $pick_task_info['order_count'] = $this->ordermodel->count(array('pick_task_id'=>$pick_task_info['id']));
                    $pick_task_info['prefix'] = isset($params['prefix']) ? $params['prefix'] : '';
                }
            }

            if($return_array === true){
                return !empty($pick_task_info) ? $pick_task_info : array();
            }else{
            	if (isset($pick_task_info['status'] ) && $pick_task_info['status'] == C('pick_task.status.finished.code')) {
            		$this->failed('此分拣任务相关订单已分拣');
            	}else{
            		$this->success($pick_task_info);
            	}
            }
        } catch (Exception $e) {
            if($return_array === true){
                return array();
            }else{
                $this->failed($e->getMessage());
            }
        }
    }
    
    /**
     * 获取配送单信息
     * @param $code_number :配送单号，post 参数
     * @param $operator_uid:执行操作user_id，post参数
     * @return Ambigous <multitype:, unknown>
     * @author yuanxiaolin@dachuwang.com
     */
    public function dispatch_info($return_array = false){
        try {
            $dispatch_info = array();
            $params = $this->datamodel->get_params();
            if(!empty($params['code_number']) && !empty($params['operator_uid'])){
                $fields = array('id','dist_number','total_price','deal_price','site_src','order_count','sku_count','created_time','line_id');
                $dispatch_info = $this->dispatchmodel->get_one($fields,array('dist_number'=>$params['code_number']));
                if(!empty($dispatch_info)){
                	$dispatch_info['prefix'] = isset($params['prefix']) ? $params['prefix'] : '';
                }
            }
            if($return_array === true){
                return !empty($dispatch_info) ? $dispatch_info : array();
            }else{
                $this->success($dispatch_info);
            }
        } catch (Exception $e) {
            if($return_array === true){
                return array();
            }else{
                $this->failed($e->getMessage());
            }
        }
    }
    
    private function assemble_orders_by_id($orders = array(),$tag = ''){
        $new_orders = array();
        if(is_array($orders) && !empty($orders)){
            foreach ($orders as $key => $value){
                $new_orders[$value['id']] = $value;
            }
        }
        return $new_orders;
    }
    private function check_orders_status($tag = '',$orders = array()){
        
        $new_orders = array();
        $order_status = '';
        if ($tag == 'picking') {
            $order_status = C('order.status.picking.code'); //要分拣的订单必须是待分拣订单
        }elseif($tag == 'dispatch'){
            $order_status = C('order.status.allocated.code'); //要配送的订单必须是已分拨订单
        }
        if ($order_status !=='' && !empty($orders)) {
            foreach ($orders as $key => $value){
                if($order_status == $value['status']){
                    $new_orders[$value['id']] = $value;
                }
            }
        }
        return $new_orders;
    }
    
}
/* End of file pda.php */
/* Location: :./application/controllers/pda.php */
