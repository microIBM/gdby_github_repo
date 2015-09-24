<?php
if( !defined('BASEPATH') ) exit('No direct script access allowed');

/**
 * BI给其他系统提供接口统一写到这里面
 * Class Interface_bi
 */
class Interface_bi extends CI_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model(['MSku', 'MCategory']);
    }

    /**
     * 接口:根据分类id获取该分类的下级分类信息
     * @author yelongyi@yelongyi.com
     * @since 2015-08-25 11:03:08
     * #param int|void category_id 分类id,不传返回所有分类
     * #param bool|void deep 默认返回传入的子级分类,如果传1,那么返回下级所有分类
     * @return json 返回json格式的信息
     */
    public function get_category_child(){
        try{
            $category_id = $this->input->post('category_id');
            if($this->input->post('deep') == 1){
                $deep = true;
            }else{
                $deep = false;
            }
            $category_list = $this->MCategory->get_category_child($category_id, $deep);
            if(empty($category_list)){
                throw new Exception('No data can be returned');
            }
            $returnArr = array();
            $i = 0;
            foreach($category_list AS $category){
                if($category['id'] == $category_id){
                    continue;//去除本身
                }
                $returnArr['data'][$i]['category_id'] = $category['id'];
                $returnArr['data'][$i]['category_name'] = $category['name'];
                $i++;
            }
            if(empty($returnArr)){
                $returnArr['data'] = array();
            }
            $this->success($returnArr);
        }catch (Exception $e){
            $this->failed($e->getMessage());
        }
    }

    /**
     * 接口:根据分类获取该分类下的所有sku信息
     * @author yelongyi@yelongyi.com
     * @since 2015-08-25 11:20:48
     * #param int category_id 分类id
     * #param int deep 默认返回该分类下的SKU,如果为1,那么返回所有该分类所属的SKU
     * #param array fields 需要取的字段信息,不传仅返回sku_number
     * @return json 返回json格式信息
     */
    public function get_sku_by_category(){
        try{
            $category_id = $this->input->post('category_id');
            if(empty($category_id)){
                throw new Exception("No incoming parameter 'category_id'");
            }
            $fields = $this->input->post('fields') ?: ['sku_number'];
            $category_list = $category_id;
            //如果deep参数为1,查询所有子孙分类的sku
            if($this->input->get('deep') == 1){
                $category_list = $this->MCategory->get_category_child($category_id, true);
                if(empty($category_list)){
                    throw new Exception('No data can be returned');
                }
                $category_list = array_column($category_list, 'id');
            }

            $sku_arr = $this->MSku->get_sku_by_category($category_list, $fields);
            if(empty($sku_arr)){
                throw new Exception('No data can be returned');
            }
            $returnArr = array();
            $i = 0;
            foreach($sku_arr AS $sku_info){
                $returnArr['data'][$i] = $sku_info;
                $i++;
            }
            if(empty($returnArr)){
                $returnArr['data'] = array();
            }
            $this->success($returnArr);
        }catch (Exception $e){
            $this->failed($e->getMessage());
        }
    }

    /**
     * 接口调用失败返回数据
     * @param string $message
     * @return json json格式的错误信息
     */
    private function failed($message = '接口调用失败'){
        $this->_return_json(
            [
                'status'  => C('status.req.failed'),
                'msg' => $message,
            ]
        );
    }

    /**
     * 接口调用成功返回数据
     * @param array $data 传入数组格式的数据
     * @return json 返回json格式的数据信息
     */
    private function success(array $data = array()){
        $data['status'] = C('status.req.success');
        $this->_return_json($data);
    }

    private function _return_json($arr) {
        if(in_array($this->input->server("HTTP_ORIGIN"), C("allowed_origins"))) {
            header('Access-Control-Allow-Origin: ' . $this->input->server("HTTP_ORIGIN"));
        } else {
            header('Access-Control-Allow-Origin: http://www.dachuwang.com');
        }
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: X-Requested-With');
        header('Cache-Control: no-cache');
        echo json_encode($arr);exit;
    }
}



/* End of file Interface_bi.php */
/* Location: ./application/controllers/Interface_bi.php */
