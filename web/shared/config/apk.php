 <?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
 //配置
 $config = array(
    'mobile' => '18888888888',

    'update_type' => array(
        '0' => '强制更新',
        '1' => '建议更新'
    ),

    'list_status' => array(
        '0' => '可用',
        '1' => '禁用'
    ),

    'action' => array(
        '0' => '禁用',
        '1' => '解禁'
    ),

    'update_effect' => array(
        'enforce' => 0,  //强制更新
        'suggest' => 1,  //建议更新
        'no'      => 2   //不更新
    )
 );
