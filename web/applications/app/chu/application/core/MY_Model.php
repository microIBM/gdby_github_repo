<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 通用model
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class MY_Model extends CI_Model {

    private $_table = NULL;

    public function __construct($table = NULL) {
        $this->_table = $table;
        parent::__construct();
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 创建记录
     */
    public function create($data) {
        $this->db->insert($this->_table, $data);
        return $this->db->insert_id();
    }

    /**
     * @author caochunhui@dachuwang.com
     * @description 批量插入
     */
    public function create_batch($data) {
        $this->db->insert_batch($this->_table, $data);
        return $this->db->affected_rows();
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取所有信息
     * @param: mixed $fields array('uid', '..')
     * @param: array $where array('name' => 'aaa', 'uid >' => $id);
     */
    public function count_group($fields = array(), $where = array(), $group_by) {
        $this->db->from($this->_table);
        if(!empty($where)) {
            foreach($where as $k => $v) {
                if($k == "in") {
                    foreach($v as $key => $value) {
                        $this->db->where_in($key, $value);
                    }
                } else {
                    $this->db->where($k, $v);
                }
            }
        }
        $this->db->group_by($group_by);
        if(empty($fields)) {
            $this->db->select("COUNT(*) num");
        } else {
            $this->db->select( implode(",", $fields) );
        }
        return $this->db->get()->result_array();
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 获取所有信息
     * @param: mixed $fields array('uid', '..')
     * @param: array $where array('name' => 'aaa', 'uid >' => $id);
     */
    public function count($where = array()) {
        $this->db->from($this->_table);
        if(!empty($where)) {
            if(isset($where['like'])) {
                foreach($where['like'] as $k => $v) {
                    $this->db->like($k, $v);
                }
                unset($where['like']);
            }
            if(isset($where['in'])) {
                foreach($where['in'] as $k => $v) {
                    $this->db->where_in($k, $v);
                }
                unset($where['in']);
            }
            if(isset($where['not_in'])) {
                foreach($where['not_in'] as $k => $v) {
                    $this->db->where_not_in($k, $v);
                }
                unset($where['not_in']);
            }
            if($where){
                $this->db->where($where);
            }
        }
        return $this->db->count_all_results();
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 设置status为0
     */
    protected function delete($query) {
        if($this->db->where($query)->update($this->_table,array('status' => 0))) {
                $info = array(
                    'status'    => TRUE,
                    'message'   => '删除成功'
                );
            } else {
            }
        $this->_return_json($info);
    }

    /**
     * 假删除，只更新status为已删除状态
     * @author yugang@ymt360.com
     * @since 2015-02-04
     */
    public function false_delete($where) {
        if(empty($where)) {
            return FALSE;
        }
        // 设置条件
        if(isset($where['in'])) {
            foreach($where['in'] as $k => $v) {
                $this->db->where_in($k, $v);
            }
            unset($where['in']);
        }
        if(isset($where['not_in'])) {
            foreach($where['not_in'] as $k => $v) {
                $this->db->where_not_in($k, $v);
            }
            unset($where['not_in']);
        }
        if($where){
            $this->db->where($where);
        }
        // 假删除数据
        return $this->db->update($this->_table, array('status' => C('status.common.del')));
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
        $this->db->from($this->_table);
        if($fields) {
            $this->db->select($fields);
        }
        if(isset($where['like'])) {
            foreach($where['like'] as $k => $v) {
                $this->db->like($k, $v);
            }
            unset($where['like']);
        }
        if(isset($where['in'])) {
            foreach($where['in'] as $k => $v) {
                $this->db->where_in($k, $v);
            }
            unset($where['in']);
        }
        if(isset($where['not_in'])) {
            foreach($where['not_in'] as $k => $v) {
                $this->db->where_not_in($k, $v);
            }
            unset($where['not_in']);
        }
        if($where){
            $this->db->where($where);
        }
        if($order_by) {
            foreach($order_by as $k => $v) {
                $this->db->order_by($k, $v);
            }
        }
        if($group_by) {
            $this->db->group_by($group_by);
        }
        if($pagesize > 0) {
            $this->db->limit($pagesize, $offset);
        }
        $result = $this->db->get();
        return $result->result_array();
    }

    /**
     * 查询符合某个字段要求的结果
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-06
     */
    public function get_by($name, $value) {
        if(!empty($value) && is_array($value)) {
            $this->db->where_in($name, $value);
        } else {
            $this->db->where($name, $value);
        }
        return $this->db->get($this->_table)->result_array();
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 查询单条记录
     */
    public function get_one($fields, $query, $order_by='') {
        if(is_array($fields)) {
            $fields = implode(',', $fields);
        }
        if(isset($query['in'])) {
            foreach($query['in'] as $k => $v) {
                $this->db->where_in($k, $v);
            }
            unset($query['in']);
        }
        if(isset($query['not_in'])) {
            foreach($query['not_in'] as $k => $v) {
                $this->db->where_not_in($k, $v);
            }
            unset($query['not_in']);
        }

        if($query){
            $this->db->where($query);
        }
       
        $this->db->from($this->_table)->select($fields);

        if($order_by) {
            $this->db->order_by($order_by);
        }
        $result = $this->db->get();
        if($result) {
            $data = $result->result_array();
        }
        if(!isset($data[0])) {
            return $data;
        }
        return $data[0];
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description 更新信息
     */
    public function update_info($data, $where) {
        $this->db->where($where);
        $data['updated_time'] = $this->input->server("REQUEST_TIME");
        $result = $this->db->update($this->_table, $data);
        return $result;
    }

    /**
     * @author: liaoxianwen@ymt360.com
     * @description
     * @param: array arr 需要转成json的数组
     */
    public function _return_json($arr) {
        header('Access-Control-Allow-Origin: *');

        header('Access-Control-Allow-Headers: X-Requested-With');
        echo  json_encode($arr);exit;
    }

    /**
     * get
     * @return array
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function get($id){
        return $this->get_by('id', $id);
    }

    /**
     * 通用的查询条件
     * @author: caiyilong@ymt360.com
     * @version: 1.0.0
     * @since: 2015-01-06
     * @query: array eg.  array( 'foo_key1' => 'bar_value1', 'foo_key2' => 'bar_value2' )
     */
    public function query($where = array(), $start = 0, $size = 0, $order_by = array()) {
        foreach($where as $key => $value) {
            if(!empty($value) && is_array($value)) {
                $this->db->where_in($key, $value);
            } else if(!is_array($value)){
                $this->db->where($key, $value);
            }
        }
        if($size > 0) {
            $this->db->limit($size, $start);
        }
        foreach($order_by as $field => $order) {
            $this->db->order_by($field, $order);
        }
        $res = $this->db->get($this->_table)->result_array();
        return $res;
    }

    /**
     * gets_by
     * @return array
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function gets_by($name_arr, $value_arr, $order = array(), $page_size = 0, $offset = 0) {
        foreach ($name_arr as $key => $name) {
            if(!empty($value_arr[$key]) && is_array($value_arr[$key])) {
                $this->db->where_in($name, $value_arr[$key]);
            } else if(!is_array($value_arr[$key])) {
                $this->db->where($name, $value_arr[$key]);
            }
        }
        foreach($order as $key => $value) {
            $this->db->order_by($key, $value);
        }
        if($page_size != 0) {
            $this->db->limit($page_size, $offset);
        }
        return $this->db->get($this->_table)->result_array();
    }

    /**
     * count_by
     * @return int
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function count_by($name_arr, $value_arr) {
        foreach ($name_arr as $key => $name) {
            if(is_array($value_arr[$key])) {
                $this->db->where_in($name, $value_arr[$key]);
            } else {
                $this->db->where($name, $value_arr[$key]);
            }
        }
        return $this->db->count_all_results($this->_table);
    }

    /**
     * lists_by
     * @return void
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function lists_by($name, $order = 'DESC', $limit = 10) {
        return $this->db->order_by($name, $order)->limit($limit)->get($this->_table)->result_array();
    }

    /**
     * update
     * @return void
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function update($id, $data) {
        return call_user_func_array(array($this, 'update_by'), array('id', $id, $data));
    }

    /**
     * update_by
     * @return void
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function update_by($name, $value, $data) {
        return $this->db->update($this->_table, $data, array($name => $value));
    }

    /**
     * add_ignore
     * @return boolean
     * @author Dennis( yuantaotao@gmail.com )
     **/
    public function add_ignore($data) {
        $insert_query = $this->db->insert_string($this->_table, $data);
        $insert_query = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $insert_query);
        return $this->db->query($insert_query);
    }

    public function delete_by($where) {
        $this->db->delete($this->_table, $where);
    }
}
/* End of file MY_Model.php */
/* Location: :./application/models/MY_Model.php */
