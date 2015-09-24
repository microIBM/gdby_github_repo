<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 仓库基础服务
 * @author yugang@dachuwang.com
 * @version: 1.0.0
 * @since: 2015-04-09
 */
class Warehouse extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model(
            array(
                'MLine',
                'MLocation',
            )
        );
        $this->load->library(array('form_validation'));
        // 激活分析器以调试程序
        // $this->output->enable_profiler(TRUE);
    }

    /**
     * 返回仓库列表对应的城市列表
     * @author yugang@dachuwang.com
     * @since 2015-04-09
     */
    public function get_warehouse_cities() {
        // 查询仓库列表
        $warehouse_ids = $this->input->post('warehouse_ids', TRUE);
        $warehouse_list = $this->MLine->get_lists('distinct(warehouse_id) as warehouse_id, location_id, warehouse_name', array('in' => array('warehouse_id' => $warehouse_ids)));
        // 查询不到仓库
        if(empty($warehouse_list)){
            $this->_return(FALSE);
        }

        $location_ids = array_column($warehouse_list, 'location_id');
        $location_ids = array_unique($location_ids);
        $location_list = $this->MLocation->get_lists('name', array('in' => array('id' => $location_ids)));
        $location_names = array_column($location_list, 'name');
        $location_map = array_combine($location_ids, $location_names);
        foreach ($warehouse_list as &$warehouse) {
            $warehouse['location_name'] = $location_map[$warehouse['location_id']];
        }

        // 返回结果
        $this->_return_json(
            array(
                'status'     => C('status.req.success'),
                'list'       => $warehouse_list,
            )
        );
    }
}

/* End of file warehouse.php */
/* Location: :./application/controllers/warehouse.php */
