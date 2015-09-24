<?php
/**
 * some under hood work for ymt360
 *
 * @author Dennis( yuantaotao@gmail.com )
 * @version 3
 * @copyright Dennis( yuantaotao@gmail.com ), 
 * @package ymt360
 **/

class MY_Loader extends CI_Loader
{
}

//provide auto memcache ability for Controllers & Libraries & Models
require_once(BASEPATH.'../shared/libraries/memauto.php');
