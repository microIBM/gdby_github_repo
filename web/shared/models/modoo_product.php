<?php

class Modoo_product extends ODOO_Model {
      
    public function __construct() {
        parent::__construct();
    }

    /*
     * @function  product_template_isset    判断该产品的模版是否存在
     * @param     $template_name            模版名称
     * @param     $attribute_value_id       该产品的模版id
     * @param     $category_id              该产品模版所属的父级id
     * @return    array() or boolean        返回类型
     * @author    caojingfu@dachuwang.com   创建人
     * @createtime 2015-03-17               创建时间
     */

    public function product_template_isset($template_name = '', $attribute_value_id = array(), $category_id = 0) {
        
        //找出规格数组中需要的数据
        $attribute = array();
        foreach($attribute_value_id as $key => $value) {
            $attribute[] = $value['attribute_id'];
        }
        
        //对数组中的编号进行排序
        sort($attribute);
       
        //查找是否存在该产品模版
        $search_product_template_id = $this->exe_rpc_call('product.template', 'search_read', array(array(array("name", "=", "$template_name"), array("categ_id", "=", $category_id))));
        
        //遍历每一个符合相同父类路径下面的相同名字的模版
        foreach($search_product_template_id as $key => $value) {
              
              //找出每一个模版下面的属性
              $product_attribute_line = $this->exe_rpc_call('product.attribute.line', 'search_read', array(array(array("product_tmpl_id", "=", $value['id']))));
              
              //每一个符合条件的模版下面的所有属性编号
              $attribute_line=array();
              foreach($product_attribute_line as $line_key => $line_value) {
                  $attribute_line[] = $line_value['attribute_id'][0];
              }
              //对符合条件的模版下面的属性编号排序
              sort($attribute_line);
              
              //如果当前模版的属性编号和传进来的属性编号进行比较，如果返回值为空，则证明存在该产品模版
              if( (count($attribute_line) == count($attribute)) && empty(array_diff_assoc($attribute_line, $attribute)) ) {
                     return $value['id'];
              }
        }
        
        return false;

    }


    /*
     * @function   update_product_by_active  通过default_code更新产品是否可用
     * @param      $default_code             内部货号
     * @return     boolean                   返回值
     * @author     caojingfu@dachuwang.com   创建人
     * @createtime 2015-03-17                创建时间
     */

    public function update_product_by_active($default_code) {
        
        //查找产品中所有等于$default_code值的内部货号
        $product_infomation = $this->exe_rpc_call('product.product', 'search_read', array(array(array("default_code", "=", $default_code))));
        
        //找到符合条件的产品
        if(!empty($product_infomation)) {
            //更新符合条件的产品
            foreach($product_infomation as $infomation_key => $infomation_value) {
                $this->exe_rpc_call('product.product', 'write', array(array($infomation_value['id']), array("active"=>false)));
            }
            return true;
        }
        return false;
    }


    
    public function get_product_id_by_code($default_code) {
        //根据default_code查找商品编号
        
        $product_product_infomation = $this->exe_rpc_call('product.product','search_read',
            array(array(array("default_code","=","$default_code"))),
            array('fields'=>array('id', 'partner_ref', 'uom_id','product_tmpl_id')));

        return $product_product_infomation;
    }

    public function batch_code_to_id($codes) {
        //批量根据货号查询商品product id
        
        $ids = $this->exe_rpc_call('product.product','search_read',
            array(array(array("default_code","in",$codes))),
            array('fields'=>array('id','default_code','partner_ref', 'uom_id','product_tmpl_id')));

        return $ids;
    }


    /*
     * @function    create_product            创建产品
     * @param       $template_category        模版父路径
     * @param       $template_name            模版名称
     * @param       $attribute_values         规格参数格式
     * @param       $default_code             产品编号
     * @return      $product_product_id       返回值
     * @author      caojingfu@dachuwang.com   创建人
     * @createtime  2015-03-17                创建时间
     */

    public function create_product($template_id = 0, $name = '', $attribute_values = array(), $code = '', $category_id = 0) {
        
        //初始化数组
        $attribute_value_infomation = array();
        $attribute_infomation_len = count($attribute_values);
            
        //获取该规格的属性的编号，组成数组
	    for($i=0 ; $i<$attribute_infomation_len ; $i++) {
            $attribute_value_infomation[] = $attribute_values[$i]['attribute_value_id'];
	    }
        
        //去除重复数值
	    $attribute_value_infomation_flip = array_unique($attribute_value_infomation);
	    //拼接odoo中many-many格式的数组
	    $arrayinfo = 
	      	     array(array(
                            
                            'product_tmpl_id' => $template_id,
	      	        	 	'attribute_value_ids' => array(array(6, 0, $attribute_value_infomation_flip)),
	      	        	 	'default_code' => $code
	      	        	 	)
	      	        );
	      	     
	    $product_product_id = $this->exe_rpc_call('product.product', 'create', $arrayinfo);
        
        return $product_product_id;

    }
    /* @function      get_product_info          获取商品信息
     * @param         $args                     参数一
     * @param         $kwargs                   参数二
     * @return                                  返回类型
     * @author        zhourui@dachuwang.com     创建人
     * @createtime    2015-04-08                创建时间
     * */
    public function get_product_info($args, $kwargs) {
        $ob_name = 'product.product';
        $action = 'search_read';
        $product_info = $this->exe_rpc_call($ob_name, $action, $args, $kwargs);
        return $product_info; 
    }

/*
     * @function      update_product            更新产品
     * @param         $attribute_value_ids      product.attribute.value对象中的id编号数组
     * @param         $code                     内部货号
     * @return                                  返回类型
     * @author        caojingfu@dachuwang.com   创建人
     * @createtime    2015-03-20                创建时间
     */

    public function update_product($attribute_value_ids,$code) {

        $product_product_id = $this->exe_rpc_call('product.product', 'search_read', array(array(array("default_code", "=", $code))));
       
        $product_product_id = $this->exe_rpc_call('product.product', 'write',
             array(array($product_product_id[0]['id']),
             array(
                 "attribute_value_ids"=>
                                        array(
                                                array(
                                                        6, false, $attribute_value_ids
                                                    )
                                        )
                                       
                 )
             ));
        return $product_product_id;
    }

 }

 
/* End of file modoo_product.php */
/* Location: ./web/shared/models/modoo_product.php */
