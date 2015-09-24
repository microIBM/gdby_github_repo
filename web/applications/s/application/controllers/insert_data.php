<?php
if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 向指定表批量插入数据通用方法
 * Class Insert_data
 */
class Insert_data extends MY_Controller{

    public function __construct() {
        parent::__construct();
    }

    /**
     * 插入方法
     * #param string table 表名，如:"sku"
     * #data array 需要插入的数据数组
     * @return mixed
     */
    public function insert(){
        try{
            $table = $this->input->post('table');
            $data = $this->input->post('data');
            $model_name = 'M' . ucfirst($table);
            if(empty($data) || !is_array($data)){
                $msg['type'] = '没有处理';
                $msg['insert_count'] = 0;
                $msg['update_count'] = 0;
                return $this->success($msg);
            }
            $this->load->model($model_name);
            if (!class_exists($model_name)) {
                throw new Exception($model_name . '模型加载失败，请检查。');
            }
            $result = $this->$model_name->update_statics($data);
            return $this->success($result);
        }catch(Exception $e){
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
}