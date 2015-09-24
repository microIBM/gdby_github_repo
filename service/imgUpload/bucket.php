<?php

$con = mysql_connect("10.10.6.183", "ecun", "ecun001");

if ( !$con ) {
    echo "Cann't connect to MySQL.";
    exit(-1);
}
mysql_query("SET NAMES utf8;");
mysql_select_db("d_dachuwang");

$query_sql = "select bucket
    from `t_img_upload` where
    status = 1;";

$buckets = array();
$query_result = mysql_query($query_sql);
while($row = mysql_fetch_array($query_result))
{
    $buckets[] = $row['bucket'];
}
mysql_close($con);
