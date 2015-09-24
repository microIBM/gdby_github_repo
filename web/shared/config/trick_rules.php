<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
// description' => '拉新5-23~5-27'
$config = array(
    'chu' => array(
        array(
            'valid_time' => strtotime('2015-05-23'),
            'invalid_time' => strtotime('2015-05-27'),
            'title' => '新用户每天首单满199元立减20元',
            'deliver_date' => strtotime('2015-05-28'),
            'require_amount' => 19900,
            'minus_amount' => 2000,
            'location_id' => 0
        ),
        // 5-28日-5-31日
        array(
            'valid_time' => strtotime('2015-05-28'),
            'invalid_time' => strtotime('2015-05-31'),
            'deliver_date' => strtotime('2015-06-01'),
            'title' => '每天首单满199元立减10元, 最晚配送时间2015-06-01',
            'require_amount' => 19900,
            'minus_amount' => 1000,
            'location_id' => 0
        ),
        array(
            'valid_time' => strtotime('2015-05-28'),
            'invalid_time' => strtotime('2015-05-31'),
            'deliver_date' => strtotime('2015-06-01'),
            'title' => '每天首单满499元立减60元, 最晚配送时间2015-06-01',
            'require_amount' => 49900,
            'minus_amount' => 6000,
            'location_id' => 0
        ),
        array(
            'valid_time' => strtotime('2015-05-28'),
            'invalid_time' => strtotime('2015-05-31'),
            'deliver_date' => strtotime('2015-06-01'),
            'title' => '每天首单满999元立减200元, 最晚配送时间2015-06-01',
            'require_amount' => 99900,
            'minus_amount' => 20000,
            'location_id' => 0
        )
    ),
    // 28-31分类控制
    'promo' => array(
        'valid_time' => strtotime('2015-05-28'),
        'invalid_time' => strtotime('2015-05-31'),
        'deliver_date' => strtotime('2015-06-01'),
        'chu' => array(
            array(
                'title' => '食用油',
                'limit' => 3,
                'id' => array(
                    11, 12, 13, 20
                )
            ),
            array(
                'title' => '面粉',
                'limit' => 5,
                'id' => array(
                    15
                )
            ),
            array(
                'title' => '酒水饮料',
                'limit' => 5,
                'id' => array(
                    305,306,307,308,309
                )
            ),
        )
    )
);
