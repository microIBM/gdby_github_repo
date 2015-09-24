<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 工具类
 * @author yugang@dachuwang.com
 * @since 2015-04-01
 */
class Common_tool extends MY_Controller {
    public function __construct () {
        parent::__construct();
    }
    /**
     * 生成Markdown语法格式的数据字典
     * @author yugang@dachuwang.com
     * @since 2015-04-01
     */
    public function generate_datadic($db_name = 'd_dachuwang_online_bak', $table_name = '') {
        // 生成具体表的数据字典
        if(!empty($table_name)) {
            $this->_gen_table($table_name);
            return;
        }
        // 查询出数据库中所有的表
        $tables = $this->db->query('SHOW TABLES FROM ' . $db_name)->result_array();
        foreach ($tables as $v) {
            $table = $v['Tables_in_d_dachuwang_online_bak'];
            $this->_gen_table($table);
        }
    }
    /**
     * 生成一个表的markdown语法的数据字典
     * @author yugang
     * @since 2015-04-01
     */
    private function _gen_table($table_name) {
        $fields = $this->db->query('SHOW FULL FIELDS FROM ' . $table_name)->result_array();
        $num = 1;
        // 查询出表的中文备注名
        $tn = $this->db->query('SHOW CREATE TABLE ' . $table_name)->result_array()[0]['Create Table'];
        $pos = strpos($tn, 'COMMENT=');
        $tn_comment = '';
        if($pos > 0){
            $tn_comment = substr($tn, $pos + 9);
            $tn_comment = rtrim($tn_comment, '\'');
        }
        // 输出表名
        echo '### ' . $table_name . ' ' . $tn_comment . '<br><br>';
        // 输出表格标题栏
        echo ' 编号 | 字段名称 | 字段类型 | 允许为空 | 字段说明 <br>';
        // echo '^ 编号 ^ 字段名称 ^ 字段类型 ^ 允许为空 ^ 字段说明 ^<br>';
        echo ' :-----------: | :----------- | :----------- | :----------- | :-----------  <br>';
        // 输出表中每一个字段详情
        foreach ($fields as $field) {
            echo $num++ . ' |  ' . $field['Field'] . ' |  ' . $field['Type'] . ' |  ' . $field['Null'] . ' |  ' . $field['Comment']. '  <br>';
        }
        echo '<br>  <br>';
    }
}
/* End of file common_tool.php */
/* Location: ./application/controllers/common_tool.php */
