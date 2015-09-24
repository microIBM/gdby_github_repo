<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
$config = array(
    'status' => array(
        'all' => array(
            'code' => - 1,
            'msg' => '全部'
        ),
        'wait_logistics' => array(
            'code' => 1,
            'msg' => '待物流处理'
        ),
        'wait_finance' => array(
            'code' => 2,
            'msg' => '待财务处理'
        ),
        'wait_operator' => array(
            'code' => 3,
            'msg' => '待客服确认'
        ),
        'finish' => array(
            'code' => 4,
            'msg' => '已处理'
        ),
        'closed' => array(
            'code' => 0,
            'msg' => '已关闭'
        )
    ),
    
    'reason' => array(
        'quality' => array(
            'code' => 1,
            'msg' => '质量问题'
        ),
        'leakage' => array(
            'code' => 2,
            'msg' => '漏单问题'
        ),
        'wrong' => array(
            'code' => 3,
            'msg' => '送错货'
        ),
        'deliver' => array(
            'code' => 4,
            'msg' => '配送不及时'
        ),
        'customer' => array(
            'code' => 5,
            'msg' => '客户原因'
        ),
        'other' => array(
            'code' => 6,
            'msg' => '其他'
        )
    ),
    
    'deal_method' => array(
        'storage' => array(
            'code' => 1,
            'msg' => '退货入库'
        ),
        'loss' => array(
            'code' => 2,
            'msg' => '就地报损'
        )
    ),
    
    'refund_methods' => array(
        'weixin' => array(
            'code' => 0,
            'msg' => '微信退款'
        ),
        'bank' => array(
            'code' => 1,
            'msg' => '银行退款'
        )
    )
);
