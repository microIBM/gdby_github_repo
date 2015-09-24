<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * 自动加载辅助函数
 * @author fengzbao@qq.com
 * @copyright Copyright (c) fzb.me
 * @version $Id:1.0.0, spl_autoload_helper.php, 2015-08-21 15:30 created (updated)$
 */
require_once BASEPATH . '../shared/libraries/spl_autoload.php';
function register_autoloader()
{
    $autoloader = new Spl_autoload();
    $autoloader->register();
}
function unregister_autoloader()
{
    $autoloader = new Spl_autoload();
    $autoloader->unregister();
}