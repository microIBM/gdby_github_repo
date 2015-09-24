<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 部门操作model
 * @author: yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-03-04
 */
class MDepartment extends MY_Model {
    use MemAuto;

    private $table = 't_department';

    public function __construct() {
        parent::__construct($this->table);
    }

    /**
     * 递归修改当前部门的所有子部门的path和level
     * @param parent_id 当前部门id
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function update_children($parent_id) {
        $parent = $this->get_one('*', array('id' => $parent_id));
        if(!$parent) {
            return FALSE;
        }
        $list = $this->get_lists('*', array('status' => C('status.common.success')));
        $this->_update_children($list, $parent['id'], $parent['path'], $parent['level']);
    }

    /**
     * 递归修改当前部门的所有子部门的path和level
     * @param list 所有部门
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    private function _update_children($list, $parent_id, $parent_path, $parent_level){
        foreach ($list as $k => $v) {
            if($v['parent_id'] == $parent_id) {
                $data = array(
                    'path'  => $parent_path . $v['id'] . '.',
                    'level' => $parent_level + 1,
                );
                // 更新path和level
                $this->update_by('id', $v['id'], $data);
                // 继续向下递归查找
                $this->_update_children($list, $v['id'], $data['path'], $data['level']);
            }
        }
    }

    /**
     * 递归获取下级部门ID列表
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    public function get_children($parent_id) {
        $parent = $this->get_one('*', array('id' => $parent_id));
        if(!$parent) {
            return FALSE;
        }
        $list = $this->get_lists('*', array('status' => C('status.common.success')));
        return $this->_get_children($list, $parent_id, TRUE);
    }

    /**
     * 递归获取下级部门ID列表
     * @author yugang@dachuwang.com
     * @since 2015-03-05
     */
    private function _get_children($list, $parent_id = 0, $is_clear = FALSE) {
        static $ret = array();
        // 如果是第一次进入递归先清空数组
        if($is_clear) {
            $ret = array();
        }
        foreach ($list as $k => $v) {
            if($v['parent_id'] == $parent_id) {
                $ret[] = $v['id'];
                $this->_get_children($list, $v['id']);
            }
        }

        return $ret;
    }

}

/* End of file mdepartment.php */
/* Location: :./application/models/mdepartment.php */
