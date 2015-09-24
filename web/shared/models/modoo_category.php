<?php

class Modoo_category extends ODOO_Model{
    
    public function __construct() {

        parent::__construct();
      
    }

      
    /*
     * @function    create_category_by_path    创建一个分类
     * @param       $category_name             创建分类的名称
     * @param       $parent_path               父级路径
     * @return      $parent_id                 返回类型
     * @author      caojingfu@dachuwang.com    创建人
     * @createtime  2015-03-17                 创建时间
     */

    public function create_category_by_path($category_name = '',$parent_path = '') {
        
        //判断是不是根路径
        if(!empty($parent_path)) {
            $category_road = $parent_path . ',' . $category_name;
            $parent_path_array = explode(',', $category_road);
        }else{
            $parent_path_array=array($category_name);
        }
          
        $parent_id = false;

        //遍历创建所组合的类别
        foreach($parent_path_array as $parent_path_key => $parent_path_value) {
            $parent_path_value = trim($parent_path_value);
            $search_category = $this->exe_rpc_call('product.category', 'search_read', array(array(array("name", "=", "$parent_path_value"), array("parent_id", "=", $parent_id))));

            if(empty($search_category)) {
                $create_id_by_category_name = $this->exe_rpc_call('product.category', 'create', array(array("name"=>"$parent_path_value", "parent_id"=>$parent_id)));
                $parent_id = $create_id_by_category_name;
            }else{
                $parent_id = $search_category[0]['id'];
            }
        }
          
        return $parent_id;

    }

}

?>
