<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Fix_line extends MY_Controller {
    public $_customer_ids = array();
    public $_dachu_daguo_id = array('105' => 103,'42' => 40,'66' => 64);

    public function __construct () {
        parent::__construct();
        $this->load->model(
            array(
                'mline',
                'mcustomer',
                'morder',
                'msuborder',
            )
        );
    }
    public function parse_csv($csvpath = null){
        isset($csvpath) OR die('select a input file');
        $this->input->is_cli_request() OR die('not cli request');
        $path = APPPATH . 'data/' . $csvpath;
        file_exists($path) OR die('file not exist');
        $handle = fopen($path, 'r');
        $data = array();
        while(!feof($handle)) {
            $data[] = fgetcsv($handle);
        }
        //转化格式
        $data = eval('return ' . iconv('gbk','utf-8//ignore', var_export($data, true)) . ';');
        return $data; 
    }
    //处理某个线路
    public function fix_dachu_line($lineinfo){
        if(empty($lineinfo['site_src'])){
            //为空
            return ;
        }
        $lineId = intval($lineinfo[0]);
        $oldName = $lineinfo[1];
        $newName = $lineinfo[2];
        $sitesrc = $lineinfo['site_src'];
        //获取线路的具体站点 大厨 大果
        if($sitesrc == '1'){
            //大厨
            //将名字改为新名字
            $updateRes = $this->mline->update_info(
                    array('name' => $newName,'full_name' => $newName,'description' => $newName),
                    array('id' => $lineId));
            if($updateRes !== true){
                echo '更新大厨线路名字失败:'.$newName."\n";
            }
        }
    }
    public function fix_daguo_line($lineinfo){

        $lineId = intval($lineinfo[0]);
        $oldName = $lineinfo[1];
        $newName = $lineinfo[2];
        $sitesrc = $lineinfo['site_src'];
        //大果
        if($sitesrc == '2'){
            //大果
            $lines = $this->mline->get_lists(array('id'),array('name' => $newName,'site_src' => '1' ));
            if(empty($lines)){
                //将名字改为新名字
                $updateRes = $this->mline->update_info(
                         array('name' => $newName,'full_name' => $newName,'description' => $newName),
                         array('id' => $lineId));
            }else{
                //会不会有多个? 
                if(count($lines) == 1){
                    $dachu_line_id =  $lines[0]['id'];
                }else{
                    $dachu_line_id = $this->_dachu_daguo_id[$lineId];
                    echo "出现多了匹配线路,$newName \n";
                }
                $this->fix_daguo_customer($lineId,$dachu_line_id);
                return $dachu_line_id;
            }
        }
        return null;
    }
    //修复大果用户数据
    public function fix_daguo_customer($daguo_lineid,$dachu_lineid){
        echo "开始修复大果线路 line_id $daguo_lineid => $dachu_lineid ";
        //获取相应的线路客户
        $customerlist = $this->mcustomer->get_lists(array('id'),array('line_id' => $daguo_lineid));
        echo " 共计影响用户".count($customerlist)."\n";
        if(!empty($customerlist)){
            $customerIds = array_column($customerlist,'id');
            //修复customer line id
            $this->mcustomer->db->where_in('id', $customerIds);
            $result = $this->mcustomer->db->update('t_customer', array('line_id' => $dachu_lineid));
            $this->_customer_ids = array_merge($this->_customer_ids,$customerIds);
        }
    }
    //fix 大果订单
    public function fix_daguo_order(){
        echo "开始修复用户的大果订单数据 \n";
        echo "共涉及用户".count($this->_customer_ids) ."条\n";
        foreach($this->_customer_ids as $value){
            $orderLists = $this->get_daguo_order($value);
            $lineInfo = $this->mcustomer->get_one(array('line_id'),array('id' => $value));
            $lineId = $lineInfo['line_id'];
            if(!empty($orderLists)){
                foreach($orderLists as $item){
                   echo "修复订单，订单number:".$item['order_number']."\n";
                   $this->fix_order_line($item['id'],$lineId);
                }
            }
            
        }
    }
    public function fix_order_line($order_id,$line_id){
          $this->morder->update_info(array('line_id' => $line_id),array('id' => $order_id)); 
          $this->msuborder->update_info(array('line_id' => $line_id),array('order_id' => $order_id)); 
    }

    public function get_daguo_order($customer_id){
        $this->morder->db->from('t_order');
        $this->morder->db->where('user_id =',$customer_id);
        $this->morder->db->where('deliver_date >',1435766400);
        $result = $this->morder->db->get();
        $orderLists = $result->result_array();
        return $orderLists;

    }
    /*
     * 第一行的描述一定要有
     *要求先把excel弄成：地点，skuID，名称
     */
    public function fix($csvpath = null) {
        //解析csv
        echo "解析csv 文件\n";
        $linedata = $this->parse_csv($csvpath);
        echo "解析完毕\n";
        echo "补充线路信息\n";
        //添加线路归属于某个系统
        foreach($linedata as $key => $value){
            $lineId = intval($value[0]);
            $linesite = $this->mline->get_one(array('site_src'),array('id' => $lineId));
            if(!empty($linesite)){
                 $linedata[$key]['site_src'] = $linesite['site_src'];
            }else{
                echo "查不到线路 lineid 为  $lineId \n";
                //测试线路 指定为大厨
                $linedata[$key]['site_src'] = 1;
            }
        }
        echo "补充完毕\n";
        //首先修复大厨线路
        echo "开始修复大厨数据\n";
        foreach($linedata as $key => $value){
            $this->fix_dachu_line($value);
        }
        echo "大厨数据修复完毕\n";
        //修复大果线路
        echo "修复大果线路\n";
        foreach($linedata as $key => $value){
            $this->fix_daguo_line($value);
        }
        $this->fix_daguo_customer(65,64);
        $this->fix_daguo_customer(39,40);
        $this->fix_daguo_customer(104,103);
        echo "大果数据修复完毕 \n"; 
        echo "修复用户订单数据 \n";
        $this->fix_daguo_order();
        exit();
    }
}

/* End of file fix_order_city.php */
/* Location: ./application/controllers/fix_order_city.php */
