<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 友商爬虫信息类
 * @author: zhangxiao@dachuwang.com
 * @version: 1.0.0
 * @since: 15-8-19
 */
class Anti_products extends MY_Controller{

    public function __construct(){
        parent::__construct();
        $this->load->model(['MAnti_products', 'MAnti_product_sku']);
    }

    public function get_cate_info(){
        $city_id = $this->input->get_post('city_id', TRUE);
        $site_id = $this->input->get_post('site_id', TRUE);
        if ($city_id === FALSE || $site_id === FALSE) {
            $this->_return_json(array(
                'status' => C('status.req.failed'),
                'msg' => 'city_id or site_id cannot be empty!',
            ));
        }

        $fields = array('distinct(cate) as cate');
        $where = array(
            'city_id' => $city_id,
            'site_id' => $site_id,
        );
        $order_by = array('cate' => 'DESC');
        $result = $this->MAnti_products->get_lists($fields, $where, $order_by);
        if (!$result) {
            $result = array();
        } else {
            $result = array_column($result, 'cate');
        }
        $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => '请求成功',
            'data' => $result
        ));
    }

    /**
     * 批量关联prod_id和sku_number
     */
    public function add_sku(){
        $prod_sku_lists = $this->input->get_post('prod_sku_lists');
        if (empty($prod_sku_lists) || !is_array($prod_sku_lists)) {
            $this->_return_json(array(
                'status' => C('status.req.failed'),
                'msg' => 'prod_sku_lists cannot be empty!',
            ));
        }
        $res = $this->MAnti_product_sku->replace_into($prod_sku_lists);
        $return['status'] = C('status.req.failed');
        $return['msg'] = 'related failed';
        if ($res) {
            $return['status'] = C('status.req.success');
            $return['msg'] = 'related success';
        }
        $this->_return_json($return);
    }

    /**
     * 把关联表中的一条status设置为0
     */
    public function delete_sku(){
        $delete_sku_list = $this->input->get_post('delete_sku_list');
        if (empty($delete_sku_list) || !is_array($delete_sku_list)) {
            $this->_return_json(array(
                'status' => C('status.req.failed'),
                'msg' => 'delete_sku_list cannot be empty!',
            ));
        }
        $where = array(
            'auto_id' => $delete_sku_list['auto_id'],
            'sku_number' => $delete_sku_list['sku_number'],
        );
        $res = $this->MAnti_product_sku->false_delete($where);
        $return['status'] = C('status.req.failed');
        $return['msg'] = 'delete failed';
        if ($res) {
            $return['status'] = C('status.req.success');
            $return['msg'] = 'delete success';
        }
        $this->_return_json($return);
    }

    /**
     * 通过auto_id获取sku
     */
    public function get_sku_by_prod(){
        $auto_ids = $this->input->get_post('auto_ids');
        if (empty($auto_ids) || !is_array($auto_ids)) {
            $this->_return_json(array(
                'status' => C('status.req.failed'),
                'msg' => 'auto_ids cannot be empty!',
            ));
        }

        $fields = array('sku_number', 'auto_id');
        $status = C('status.product.up');
        $where = array(
            'in' => ['auto_id' => $auto_ids],
            'status' => $status,
        );
        $result = $this->MAnti_product_sku->get_lists($fields, $where);
        $tmpArr = array();

        foreach($result AS $val){
            $tmpArr[$val['auto_id']][] = $val['sku_number'];
        }
        $returnArr = array();
        foreach($auto_ids as $val){
            if(!isset($tmpArr[$val])){
                $tmpArr[$val] = [];
            }
            $returnArr[$val][] = $tmpArr[$val] ?: [];
        }
        $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => 'request success.',
            'data' => $returnArr
        ));
    }

    /**
     * 通过sku获取auto_id
     */
    public function get_prod_by_sku(){
        $sku_number = $this->input->get_post('sku_number');
        if (empty($sku_number)) {
            $this->_return_json(array(
                'status' => C('status.req.failed'),
                'msg' => 'sku_number cannot be empty!',
            ));
        }
        $fields = array('auto_id');
        $where = array(
            'sku_number' => $sku_number,
            'status' => C('status.product.up'),
        );
        $result = $this->MAnti_product_sku->get_lists($fields, $where);
        $result = array_column($result, 'auto_id');
        if (!$result) {
            $result = array();
        }
        $this->_return_json(array(
            'status' => C('status.req.success'),
            'msg' => 'request success.',
            'data' => $result
        ));
    }


}

/* End of file anti_products.php */
/* Location: ./application/controllers/anti_products.php */
