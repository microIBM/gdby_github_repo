<?php

class Modoo_product_attribute extends ODOO_Model {
      
    public function __construct() {
        parent::__construct();
    }


    /*
     * @function    attribute_name_value_isset    判断传进来的规格二维数组是否存在
     * @param       $attribute_name_value         规格格数组
     * @param       $is_product                   为true时表示为产品，为false时表示产品模版
     * @return      array()                       返回数组格式
     * @author      caojingfu@dachuwang.com       创建人
     * @createtime  2015-03-17                    创建时间
     */
    
    public function attribute_name_value_isset($attribute_name_value = array(), $need_create_value= true) {
        
        //初始化数组
        $attributes = array();
        
        //遍历属性数组
        foreach($attribute_name_value as $i => $attributes_one_value) {
            
            //查找传进来的属性名称是否存在，如果存在查找该编号
            $attribute_ids = $this->exe_rpc_call('product.attribute', 'search', array(array(array("name", "=", "$attributes_one_value[0]"))));
                
            //如果该属性名称不存在，则创建该属性
            if(empty($attribute_ids)) {
                $attribute_id = $this->exe_rpc_call('product.attribute', 'create', array(array("name"=>$attributes_one_value[0])));   
            }else{
                $attribute_id = $attribute_ids[0];
            }
                     
            if($need_create_value) {

                //判断属性的值是否存在，如果存在取出。若不存在，创建
                $attribute_values = $this->exe_rpc_call('product.attribute.value', 'search', array(array(array("name", "=", $attributes_one_value[1]), array("attribute_id", "=", $attribute_id))));

                if(empty($attribute_values)) {
                    $attribute_value = $this->exe_rpc_call(
                                'product.attribute.value', 'create', array(array('name' => $attributes_one_value[1], 'attribute_id' => $attribute_id)));
                }else{
                    $attribute_value = $attribute_values[0];
                }
            }

            //获得属性的二维数组
            $attributes[$i]['attribute_id']=$attribute_id;
            if($need_create_value) {
                $attributes[$i]['attribute_value_id']=$attribute_value;
            }
            
        }
        return $attributes;
    } 
}

/* End of file modoo_product_attribute.php */
/* Location: ./web/shared/models/modoo_product_attribute.php */
