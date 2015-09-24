<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Odoo_category extends MY_Controller {

    public function __construct () {
    
        parent::__construct();
        //构造函数调用model或者library
        $this->load->model(array('modoo_category'));
 
    }

    
    /*
     * @function    create_category_by_path    创建无限分类
     * @param       $category_name             建立的分类的名称
     * @param       $parent_path               建立的分类的路径
     * @author      caojingfu@dachuwang.com    创建人
     * @createtime  2015-03-17                 时间
     */

    public function create_category_by_path() {
        
        $category_name = I('post.category_name');
        $parent_path = I('post.parent_path');

        //判断传进来的参数准确性，返回错误码
        if(empty($category_name)) {
            E(201, 'parameters error');
        }

        //创建无限分类
        $category_infomation = $this->modoo_category->create_category_by_path($category_name, $parent_path);
        
        //判断创建结果
        if(is_numeric($category_infomation)) {
            E(0, 'create category sucess', array('id' => $category_infomation, 'message' => 'id'));
        }else{
            E(401, 'create category error', array('message' => $category_infomation));
        }
        
    }
    
}
/* End of file category.php */
/* Location: ./application/controllers/category.php */

