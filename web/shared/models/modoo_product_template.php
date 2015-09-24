<?php
class Modoo_product_template extends ODOO_Model {

    public function __construct() {
        parent::__construct();
    }


    /*
     * @function   create_product_template  创建产品模版
     * @param      $product_template_name   产品模版名称
     * @param      $type                    类型
     * @param      $description             描述
     * @param      $price                   价格
     * @param      $attributes              规格数组
     * @param      $category_id             所属分类编号
     * @return     $product_template_id     返回类型
     * @author     caojingfu@dachuwang.com  创建人
     * @createtime 2015-03-17               创建时间
     */

    public function create_product_template($product_template_name = '', $type = 'product', $description = '', $price = 0, $attributes = array(), $category_id = 0, $default_code) {

      	//初始化数组
      	$attribute_infomation = array();
        $attribute_infomation_len = count($attributes);

        //拼装所需数组格式,操作one2many的字段
        for($i = 0;$i < $attribute_infomation_len;$i++) {
            $attribute_infomation[]=
              array(0,false,array('attribute_id' => $attributes[$i]['attribute_id'],
                                  'value_ids' => array(
                                                       array(
                                                           6, false, isset($attributes[$i]['attribute_value_id'])?
                                                                array($attributes[$i]['attribute_value_id']):
                                                                array()
                                                        )
                                                  )
                                  )
              );
      	}

        //拼装odoo many-many的所需数组格式
        $arrayinfo=array(array(
	      	        	 	  	    'name' => "$product_template_name",
	      	        	 	  	    'type' => $type,
	      	        	 	  	    'list_price' => $price,
	      	        	 	  	    'description' => $description,
	      	        	 	  	    'cost_method' => 'standard',
	      	        	 	  	    'attribute_line_ids' => $attribute_infomation,
	      	        	 	  	    'categ_id' => $category_id,
                                    'default_code' => $default_code
      	        	 	  	      )
      	        	 	  	 );


        //创建产品模版
        $product_template_id=$this->exe_rpc_call('product.template', 'create', 
	                   $arrayinfo
	      );

        //返回创建模版的信息
      	return $product_template_id;

    }


  
     /*
     * @function    update_product_template    更新产品
     * @param       $product_template_id       产品模版编号
     * @param       $name                      产品模版名字
     * @param       $update_attributes         更新的属性
     * @param       $category_id               父类编号
     * @param       $code                      内部货号
     * @param       $price                     价格
     * @param       $description               描述
     * @param       $type                      产品类型
     * @return                                 返回类型
     * @author      caojingfu@dachuwang.com    创建者
     * @createtime  2015-03-19                 创建时间
     */

    public function update_product_template($product_template_id, $name, $update_attributes, $category_id, $code, $price, $description, $type) {
         
        //选出当前模版的所有属性编号
        $product_tmpl_attribute_id = $this->exe_rpc_call('product.attribute.line', 'search_read', array(array(array("product_tmpl_id", "=", $product_template_id))));
       
        if(!empty($product_tmpl_attribute_id)) {
            //遍历当前模版的在对象中的所有ID号，把所有的符合条件的数据删除掉
            foreach($product_tmpl_attribute_id as  $key => $value) {
               $is_delete = $this->exe_rpc_call('product.attribute.line', 'unlink',
                 array(array($value['id'])));
                   if($is_delete != 1) {
                       return 'delete error, try again';
                   }
            }
        }

        //获取属性及属性值的id，不用重复的添加，并且为了已有数据的关联，不做删除操作
        $attributes = $this->modoo_product_attribute->attribute_name_value_isset($update_attributes, true);
	$attribute_value_ids = array();
        foreach($attributes as $attr) {

            $attribute_id = $attr['attribute_id'];
            $atribute_value_id = $attr['attribute_value_id'];
	    $attribute_value_ids[] = $atribute_value_id;

            //创建关联关系
            $attribute_line_ids = $this->exe_rpc_call('product.attribute.line', 'create', array(array(
                                                     "product_tmpl_id" => $product_template_id,
                                                     "attribute_id" => $attribute_id,
                                                     "value_ids" => array(array(6, false, array($atribute_value_id)))
                                              )
                                            )
            );
            if(!is_numeric($attribute_line_ids)) {
                return 'create attribute_line_ids error';
            }
        }
       
        //拼装odoo many-many的所需数组格式
        $arrayinfo=array(
                        'name' => "$name",
                        'type' => $type,
                        //'list_price' => $price, //price 价格不能更改，冗余代码防止需求变动
                        //'description' => $description, //描述字段存有产品附加信息，不能更改
                        'cost_method' => 'standard',
                        'categ_id' => $category_id,
                    );

        //更新产品模版
        $product_template_infomation = $this->exe_rpc_call('product.template', 'write',
                     array(array($product_template_id),$arrayinfo)        
        );

        //更新产品
        if(($product_template_infomation) != 1) {
            return 'update product template error!';
        }
       
        return $attribute_value_ids;
    }





        
}

/* End of file category.php */
/* Location: ./application/controllers/category.php */
