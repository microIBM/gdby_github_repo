<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Odoo_product_template extends MY_Controller {

    public function __construct () {

        parent::__construct();
        //构造函数调用model或者library
        $this->load->model(array('modoo_product_template', 'modoo_category', 'modoo_product_attribute'));

    }

    
    
    /*
     * @function    create_product_template    创建产品模版
     * @param       $name                   产品模版名称
     * @param       $category               父级路径
     * @param       $type                   类型
     * @param       $description            描述
     * @param       $price                  价格
     * @param       $attribute              规格数组
     * @return      json                       错误码
     * @author      caojingfu@dachuwang.com    创建人
     * @createtime  2015-03-17                 创建时间
     */
    
    public function create_product_template() {
       
        $name = I('post.name');
        $code = I('post.code');
        $price = I('post.price',0);
        $category = I('post.category');
        $type = I('post.type', 'product');
        $attributes = I('post.attributes/a');
        $description = I('post.description');
        
        //判断传进来的参数格式的正确性
        if(empty($product_template_name) || empty($product_template_category) || empty($default_code)) {
            E(201,'parameters error');
        }

        //拼装路径
        $parent_path = substr($product_template_category, strrpos($product_template_category, ',')+1);
        $product_template_category  = substr($product_template_category, 0, strrpos($product_template_category, ','));
            
        //判断相对应的分类是否存在
        $category_array = explode(",", $product_template_category);

        //获得最后一级分类的id号
        $category_id = $this->modoo_category->create_category_by_path($parent_path, $product_template_category);

        //查看对应的属性值是否存在
        $attributes = $this->modoo_product_attribute->attribute_name_value_isset($attributes, false);
        
        //创建产品模版，并返回该产品模版的编号
        $product_template_infomation = $this->modoo_product_template->create_product_template($product_template_name, $type, $description, $price, $attributes, $category_id, $default_code);
       
        //判断是否创建成功
        if(is_numeric($product_template_infomation)) {
            E(0, 'create product template sucess', array('id'=>$product_template_infomation));
        }else {
            E(401, 'create product template error', array('message'=>$product_template_infomation));
        }

    }

}
/* End of file product_template.php */
/* Location: ./application/controllers/product_template.php */

