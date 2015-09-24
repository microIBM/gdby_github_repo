<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Odoo_product extends MY_Controller {

    public function __construct () {

        parent::__construct();
        //构造函数调用model或者library
        $this->load->model(array('modoo_category', 'modoo_product_template', 'modoo_product', 'modoo_product_attribute'));

    }
 /*
     * @funciton     update_product            更新产品
     * @param        $category                 产品分类
     * @param        $name                     产品名字
     * @param        $code                     内部货号
     * @param        $price                    价格
     * @param        $type                     产品类型
     * @param        $description              产品描述
     * @param        $attributes               产品属性
     * @return       json                      错误码
     * @author       caojingfu@dachuwang.com   创建人
     * @createtime   2015-03-19                创建时间
     */

    public function update_product() {

        //接受curl传进来的参数
        $category = I('post.category');
        $name = I('post.name');
        $code = I('post.code');

        //创建产品所需要的参数
        $price = I('post.price',0);
        $description = I('post.description');
        $type = I('post.type', 'product');

        $attributes = I('post.attributes/a',array());

        //判断传入的数据准确性,返回错误码
        if( empty($name) || empty($category) || empty($code) || empty($type)) {
             E(201, 'parameters error');
        }
        //通过内部货号查找该产品,如果没有就转创建
        $product_infomation = $this->modoo_product->get_product_id_by_code($code);
        if(empty($product_infomation)) {
            return $this->create_product();
        }

        //判断传入的属性有没有重复值，如果有，则报错
        $attribute_is_unique = array();
        foreach($attributes as $attributes_key => $attributes_value) {
            $attribute_is_unique[] = $attributes_value[0];
        }
        $attributes_value_count = array_count_values($attribute_is_unique);
        foreach($attributes_value_count as $key => $value){
          if($value > 1){
              E(202, 'parameters same.');
            }
        }

        //拼装所属父路径的数据格式
        $category_name = substr($category, strrpos($category, ',')+1);
        $category_path  = substr($category, 0, strrpos($category, ','));

        //查找父类的编号
        $category_id = $this->modoo_category->create_category_by_path($category_name, $category_path);


        //找出产品的模版编号和产品编号
        $product_template_id = $product_infomation[0]['product_tmpl_id'][0];

        //更新产品模版
        $template_attribute_value_ids = $this->modoo_product_template->update_product_template($product_template_id, $name, $attributes, $category_id, $code, $price, $description, $type);

        if(!is_array($template_attribute_value_ids)) {
            E(501, 'update product template error!');
        }

        //更新产品
        $update_product_infomation = $this->modoo_product->update_product($template_attribute_value_ids, $code);

        //判断是否更新成功
        if($update_product_infomation != 1) {
            E(502, 'update product error');
        }else{
            E(0, 'update product sucess');
        }

    }
    /*
     * @function create_product      创建产品
     * @param    $category           所属分类的路径
     * @param    $name               产品名称
     * @param    $attributes         各属性及其值
     * @param    $code               产品内部编号
     * @return   json                返回错误码
     * @author   caojingfu@dachuwang.com 创建人
     * @createtime 2015-03-17        创建时间
     */

    public function create_product() {

        //接受curl传进来的参数
        $category = I('post.category');
        $name = I('post.name');
        $code = I('post.code');

        //创建产品所需要的参数
        $price = I('post.price',0);
        $description = I('post.description');
        $type = I('post.type', 'product');

        $attributes = I('post.attributes/a',array());

        //判断传入的数据准确性,返回错误码
        if( empty($name) || empty($category)  || empty($code) || empty($type)) {
               E(201, 'parameters error');
        }

        //判断内部货号有没有重复
        $product_infomation_by_code = $this->modoo_product->get_product_id_by_code($code);

        if(!empty($product_infomation_by_code)) {
            E(203, 'default_code same');
        }

        //判断传入的属性有没有重复值，如果有，则报错
        $attribute_is_unique = array();
        foreach($attributes as $attributes_key => $attributes_value){
            $attribute_is_unique[] = $attributes_value[0];
        }
        $attributes_value_count = array_count_values($attribute_is_unique);
        foreach($attributes_value_count as $key => $value){    
            if($value > 1){
              E(202, 'parameters same.');
            }
        }

        //拼装所属分类的数据格式
        $category_array = explode(',', $category);
        if(count($category_array) == 1) {
            //查找所属分类的编号
            $category_id = $this->modoo_category->create_category_by_path($category_array[0], '');
        }else{
            $category_name = substr($category, strrpos($category, ',')+1);
            $category_path = substr($category, 0, strrpos($category, ','));
            //查找所属分类的编号
            $category_id = $this->modoo_category->create_category_by_path($category_name, $category_path);
        }

        //查找产品属性
        $attributes = $this->modoo_product_attribute->attribute_name_value_isset($attributes, true);

        //创建产品模板，同时自动的创建出来了产品
        $template_infomation = $this->modoo_product_template->create_product_template($name, $type, $description, $price, $attributes, $category_id, $code);

        //判断产品模版是否创建成功
        if(!is_numeric($template_infomation)) {
            E(401, 'create product template error', $template_infomation);
        }else{
            E(0, 'create product sucess', $template_infomation);
        }


    }
}
/* End of file product.php */
/* Location: ./application/controllers/product.php */

