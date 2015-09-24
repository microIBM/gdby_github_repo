<?php 
ini_set('date.timezone', 'Asia/Shanghai');

/*===============开发测试环境===========*/
//define('PAY_ENV', 'test');

/*===============线上生产环境===========*/
define('PAY_ENV', 'production');

//开发调试环境
if (PAY_ENV == 'test') {
    error_reporting(E_ERROR);
//线上生产环境
}else{
    error_reporting(0);
}
