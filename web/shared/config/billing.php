<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
$config = array(
    'status' => array(
        'disabled' => array(
            'code' => - 1
        ),
        'valid' => array(
            'code' => 1
        ),
        'invalid' => array(
            'code' => 0
        ),
        'unpay' => array(
            'code' => 2,
            'msg' => '未打款'
        ),
        'prepay' => array(
            'code' => 3,
            'msg' => '对账中'
        ),
        'payed' => array(
            'code' => 4,
            'msg' => '已打款'
        ),
        'finish' => array(
            'code' => 5,
            'msg' => '已收款'
        )
    ),
    
    'shop_status' => array(
        'unpay' => array(
            'code' => 2,
            'msg' => '未结款'
        ),
        'prepay' => array(
            'code' => 3,
            'msg' => '待结款'
        ),
        'payed' => array(
            'code' => 4,
            'msg' => '待审核'
        ),
        'finish' => array(
            'code' => 5,
            'msg' => '已结款'
        )
    ),
    
    'auto' => array(
        'is_auto' => array(
            'code' => 1
        ),
        'is_not_auto' => array(
            'code' => 0
        )
    ),
	'expire_status' => array(
		'yes' => array(
            'code' => 1,
            'msg'  => '是'
		),
		'no' => array(
			'code' => 0,
            'msg'  => '否'
		)
	),
    'expire_tag' => array(
        'tag_expire' => array(
            'code' => 1
        ),
        'tag_not_expire' => array(
            'code' => 0
        )
    )
);
