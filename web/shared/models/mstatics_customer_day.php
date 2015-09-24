<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class MStatics_customer_day extends MY_Model {

    private $_table = 't_statics_customer_day';
    //time是区分数据唯一性的唯一条件
    private $_time = '';
    private $DB_BI;
    public function __construct() {
        parent::__construct($this->_table);
        $this->DB_BI = $this->load->database('d_statics', TRUE);
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
     * 获取sku每日数据
     * @param unknown $param
     * @author yuanxiaolin@dachuwang.com
     */
    public function get_day_lists($param = array()){
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
            $where['time >='] = $param['sdate'];
        }
        if(!empty($param['edate'])){
            $where['time <'] = $param['edate'];
        }
        $this->DB_BI->select('*')->from($this->_table);
        $this->DB_BI->where($where);
        $this->DB_BI->order_by('time', 'desc');
        $query = $this->DB_BI->get();
        return $query->result_array();
    }
}

/* End of file statics_sku_day */
/* Location: :./application/models/statics_sku_day.php */
