<?php
$config = array (
    'type' => array (
        'offline' => array (
            'code' => 0,
            'msg' => '货到付款'
        ),
        'weixin' => array (
            'code' => 1,
            'msg' => '微信支付'
        ),
        'bill_pay' => array(
            'code' => 2,
            'msg' => '账期支付'
        )
    ),
    'pay_types' => array(
        array(
            'code' => 0,
            'msg' => '货到付款'
        ),
        array(
            'code' => 1,
            'msg' => '微信支付'
        ),
        array(
            'code' => 2,
            'msg' => '账期支付'
        )

    ),
    'status' => array (
        'success' => array (
            'code' => 1,
            'msg' => '支付成功',
            'class' => 'label-success'
        ),
        'failed' => array (
            'code' => - 1,
            'msg' => '支付失败',
            'class' => 'label-danger'
        ),
        'waiting' => array (
            'code' => 0,
            'msg' => '未支付',
            'class' => 'label-warning'
        )
    ),
    //微信支付开通城市及灰度名单
    'open' => array (
        'beijing' => array (
            'name' => '北京',
            'id' => 804,
            'white_users' => array(
               // '18665859220','18612188241','18618461323','15201535845','13604090778'
            ),
        ),
        'shanghai' => array (
            'name' => '上海',
            'id' => 993,
            'white_users' => array(
                //'18618461323','18501361389'
            ),
        ),
        'tianjing' => array(
            'name' => '天津',
            'id' => 1206,
            'white_users'=>array(
                //'18618461323','18501361389'
            ),
        ),
        'wuhan' => array(
            'name' => '武汉',
            'id' => 1208,
            'white_users'=>array(
                //'18618461323','18501361389'
            ),
        ),
        'chengdu' => array(
            'name' => '成都',
            'id' => 1207,
            'white_users'=>array(
                //'18618461323','18501361389'
            ),
        ),
        'guangzhou' => array(
            'name' => '广州',
            'id' => 1209,
            'white_users'=>array(
                //'18618461323','18501361389'
            ),
        ),
        'changsha' => array(
            'name' => '长沙',
            'id' => 1210,
            'white_users'=>array(
                //'18618461323','18501361389'
            ),
        )
    ),

    //微信支付在每个城市的推广活动
    'events' => array(
        '804' => array(
            'event_title' => '每日首单微信支付立减',//活动推广文案
            'event_rule' => '每日首单选择微信支付,满立减活动',//活动规则描述
            'start_time' => '2015-7-14 00:00:00',//活动开始时间
            'end_time' => ' 2015-07-30 23:00:00',//活动结束时间
            'online' => 1, //活动上下线状态
            'reduce' => 5,//通用满减额，优先级高于total_reduce
            //满减组合:'满额' => '立减额'
            'total_reduce' =>array(
                '199' => 10,
            ),
        ),
        '993' => array(
            'event_title' => '每日首单微信支付立减',//活动推广文案
            'event_rule' => '每日首单选择微信支付,满立减活动',//活动规则描述
            'start_time' => '2015-06-15 00:00:00',//活动开始时间
            'end_time' => '2015-06-30 23:00:00',//活动结束时间
            'online' => 1, //活动上下线状态
            'reduce' => 5,//通用满减额，优先级高于total_reduce
            //满减组合:'满额' => '立减额'
            'total_reduce' =>array(
                '199' => 10,
            ),
        ),
        '1206' => array(
            'event_title' => '每日首单微信支付立减',//活动推广文案
            'event_rule' => '每日首单选择微信支付,满立减活动',//活动规则描述
            'start_time' => '2015-06-15 00:00:00',//活动开始时间
            'end_time' => '2015-06-30 23:00:00',//活动结束时间
            'online' => 1, //活动上下线状态
            'reduce' => 5,//通用满减额，优先级高于total_reduce
            //满减组合:'满额' => '立减额'
            'total_reduce' =>array(
                '199' => 10,
            ),
        ),
    ),
);
