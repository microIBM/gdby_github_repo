<?php

if (! defined('BASEPATH'))
    exit('No direct script access allowed') ;
/**
 * 潜在客户操作model
 * @author : zhangxiao@dachuwang.com
 * @version : 1.0.0
 * @since : 2015-04-24
 */
class MCustomer_potential extends MY_Model {
    use MemAuto;

    private $table = 't_potential_customer' ;
    
    public function __construct () {
        parent::__construct($this->table) ;
    }
    
    /**
     * 潜在客户录入时间
     * @author zhangxiao@dachuwang.com
     */
    public function get_one_cus_recordtime_by_mobile ($cus_mobile) {
        $fields = array('created_time');
        $query = array('mobile' => $cus_mobile);
    	$res = $this->get_one($fields, $query);
    	if(!$res) {
    		return FALSE;
    	}
    	return $res;
    }
}

/* End of file mcustomer_potential.php */
/* Location: :./shared/models/mcustomer_potential.php */