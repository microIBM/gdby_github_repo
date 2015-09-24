<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 角色model
 * @author: liaoxianwen@ymt360.com
 * @version: 1.0.0
 * @since: datetime
 */
class MRoles {
    use MemAuto;

    protected $roles = array(
        0   => array(
            'name'      => '管理员',
            // 为空就是全部权限
            'deny'    => array()
        ),
        // 不负责发货
        1   => array(
            'name'      => '供货老板',
            'deny'    => array(
                'user.reg', 'product.create',
                'order.create', 'order.edit',
                'order.cancel'
            )
        ),
        11  => array(
            'name'  => '供货店长',
            'deny'  => array(
                'user.reg','user.user_cancel', 
            ) 
        ),
        // 预留，暂不启用
        2   => '总采购商',
        // 负责采购
        21  => array(
            'name'  =>  '采购店长',
            'deny'  =>  array(
                'product.create', 'user.user_cancel',
                'user.create', 'product.edit',
                'product.cancel'
            )
        )
    );
    protected $role_id = NULL;

    public function __construct() {
    
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @desc 获取角色
     */
    public function get_role($role) {
        $this->role_id =  $role;
        return $this;
    }
    /**
     * @author: liaoxianwen@ymt360.com
     * @desc 权限检测
     */
    public function get_permits() {
        return $this->permits[$this->role_id];
    }
    

}

/* End of file mroles.php */
/* Location: :./application/models/mroles.php */
