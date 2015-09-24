<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MStatics_sku_week extends MY_Model {

    private $_table = 't_statics_sku_week';
    //time是区分数据唯一性的条件
    private $_time = '';
    private $DB_BI;
    public function __construct() {
        parent::__construct($this->_table);
        $this->DB_BI = $this->load->database('d_statics', TRUE);
    }

    /**
     * 向静态表批量插入或更新数据
     * @param array $data
     * @return bool
     */
    public function update_statics($data = array()) {
        if(!empty($data)) {
            $nowTime = $this->input->server("REQUEST_TIME");
            $insert_num = 0;
            $update_num = 0;
            $this->DB_BI->trans_start();
            foreach ($data as $value) {
                if(empty($value['time'])){
                    log_message('error', 'mstatics_sku_week'.print_r($value, TRUE));
                    continue;
                }
                // 1. 判断数据库中是否存在该条数据;2. 存在,更新;3. 不存在,插入
                $result = $this->DB_BI->select('*')->from($this->_table)->where('sku_number', $value['sku_number'])->where('`time`', $value['time'])->where('city_id', $value['city_id'])->where('warehouse_id', $value['warehouse_id'])->where('path', $value['path'])->get()->result_array();
                if(count($result) === 0){
                    $value['created_time'] = $nowTime;
                    $value['updated_time'] = $nowTime;
                    $this->DB_BI->insert($this->_table, $value);
                    $insert_num ++;
                }else{
                    $value['updated_time'] = $nowTime;
                    $this->DB_BI->where('sku_number', $value['sku_number'])->where('time', $value['time'])->where('city_id', $value['city_id'])->where('warehouse_id', $value['warehouse_id'])->where('path', $value['path'])->update($this->_table, $value);
                    $update_num ++;
                }
            }
            $this->DB_BI->trans_complete();
            if($this->DB_BI->trans_status() === FALSE) {
                $msg['type'] = '处理失败';
                $msg['insert_count'] = 0;
                $msg['update_count'] = 0;
            } else {
                $msg['type'] = '处理成功';
                $msg['insert_count'] = $insert_num;
                $msg['update_count'] = $update_num;
            }
            return $msg;
        } else {
            $msg['type'] = '没有处理';
            $msg['insert_count'] = 0;
            $msg['update_count'] = 0;
            return $msg;
        }
    }

    private function _insert_data($data){
        $now_time = $this->input->server("REQUEST_TIME");
        foreach ($data as &$value) {
            $value['created_time'] = $now_time;
            $value['updated_time'] = $now_time;
        }
        $this->DB_BI->insert_batch($this->_table, $data);
        $affect_rows = $this->DB_BI->affected_rows();
        if ($affect_rows) {
            return $affect_rows;
        } else {
            return FALSE;
        }
    }

    private function _update_data($data) {
        $this->DB_BI->trans_start();
        $today_date = $this->_time;
        $nowTime = $this->input->server("REQUEST_TIME");
        foreach ($data as $value) {
            //更新updated_time
            $value['updated_time'] = $nowTime;
            $this->DB_BI->where('time', $today_date);
            $this->DB_BI->where('city_id', $value['city_id']);
            $this->DB_BI->where('warehouse_id', $value['warehouse_id']);
            $this->DB_BI->where('path', $value['path']);
            $this->DB_BI->update($this->_table, $value);
        }
        $this->DB_BI->trans_complete();
        if($this->DB_BI->trans_status() === FALSE) {
            return FALSE;
        } else {
            return count($data);
        }
    }

    /**
     * 用来判断是否是新的插入
     * @param $data
     * @return bool
     */
    private function _contain_this_data($data) {
        $count = count($data);
        $this->_time = $data[0]['time'];
        $this->DB_BI->select('id')->from($this->_table);
        $this->DB_BI->where('time', $this->_time);
        $this->DB_BI->where('city_id', $data[0]['city_id']);
        $this->DB_BI->where('warehouse_id', $data[0]['warehouse_id']);
        $this->DB_BI->where("substring_index(path, '.', 2) =", substr($data[0]['path'], strpos($data[0]['path'], '.'), strpos($data[0]['path'], '.', 1)));
        $result = $this->DB_BI->get()->result_array();
        if(count($result) == $count) {
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取列表
     * @param: array fields 查询的字段
     * @param: array where 查询条件
     * @param: array join 多表查询
     * @param: string  order_by  排序
     * @param: string limit 限制查多少条
     * @param: string  group_by  分组
     * @return: array
     */
    public function get_lists($fields = array(), $where = array(), $order_by = array(), $group_by = array(), $offset = 0, $pagesize = 0) {
        if(!empty($fields)) {
            if(is_array($fields)) {
                $fields = implode(',', $fields);
            }
        } else {
            $fields = '*';
        }
        $this->DB_BI->from($this->_table);
        if($fields) {
            $this->DB_BI->select($fields, FALSE);
        }
        if(isset($where['like'])) {
            foreach($where['like'] as $k => $v) {
                $this->DB_BI->like($k, $v);
            }
            unset($where['like']);
        }
        if(isset($where['not_like'])) {
            foreach($where['not_like'] as $k => $v) {
                $this->DB_BI->not_like($k, $v);
            }
            unset($where['not_like']);
        }
        if(isset($where['in'])) {
            foreach($where['in'] as $k => $v) {
                $this->DB_BI->where_in($k, $v);
            }
            unset($where['in']);
        }
        if(isset($where['not_in'])) {
            foreach($where['not_in'] as $k => $v) {
                $this->DB_BI->where_not_in($k, $v);
            }
            unset($where['not_in']);
        }
        if(isset($where['having'])){
            foreach($where['having'] as $k => $v){
                $this->DB_BI->having($k, $v);
            }
            unset($where['having']);
        }
        if(isset($where['or'])) {
            foreach($where['or'] as $k => $v) {
                $this->DB_BI->or_where($k, $v);
            }
            unset($where['or']);
        }

        if($where){
            $this->DB_BI->where($where);
        }
        if($order_by) {
            foreach($order_by as $k => $v) {
                $this->DB_BI->order_by($k, $v);
            }
        }
        if($group_by) {
            $this->DB_BI->group_by($group_by);
        }
        if($pagesize > 0) {
            $this->DB_BI->limit($pagesize, $offset);
        }
        $result = $this->DB_BI->get();
        return $result->result_array();
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取所有信息
     * @param: mixed $fields array('uid', '..')
     * @param: array $where array('name' => 'aaa', 'uid >' => $id);
     */
    public function count($where = array()) {
        $this->DB_BI->from($this->_table);
        if(!empty($where)) {
            if(isset($where['like'])) {
                foreach($where['like'] as $k => $v) {
                    $this->DB_BI->like($k, $v);
                }
                unset($where['like']);
            }
            if(isset($where['in'])) {
                foreach($where['in'] as $k => $v) {
                    $this->DB_BI->where_in($k, $v);
                }
                unset($where['in']);
            }
            if(isset($where['or'])) {
                foreach($where['or'] as $k => $v) {
                    $this->DB_BI->or_where($k, $v);
                }
                unset($where['or']);
            }
            if(isset($where['not_in'])) {
                foreach($where['not_in'] as $k => $v) {
                    $this->DB_BI->where_not_in($k, $v);
                }
                unset($where['not_in']);
            }
            if($where){
                $this->DB_BI->where($where);
            }
        }
        return $this->DB_BI->count_all_results();
    }
    
    /**
     * 获取单个sku周总计
     * @param unknown $param
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_week_lists($param = array()){
    
        if(!empty($param['sku_number'])){
            $where['sku_number'] = $param['sku_number'];
        }
        if(!empty($param['city_id'])){
            $where['city_id'] = $param['city_id'];
        }
        if(!empty($param['warehouse_id'])){
            $where['warehouse_id'] = $param['warehouse_id'];
        }
        if(!empty($param['sdate'])){
            $where['data_date >='] = $param['sdate'];
        }
        if(!empty($param['edate'])){
            $where['data_date <'] = $param['edate'];
        }
        $this->DB_BI->select('*')->from($this->_table);
        $this->DB_BI->where($where);
        $this->DB_BI->order_by('data_date', 'desc');
    
        return $this->DB_BI->get()->result_array();
    }
}

/* End of file statics_sku_week */
/* Location: :./application/models/statics_sku_week.php */
