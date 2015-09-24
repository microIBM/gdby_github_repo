<!-- 大厨、大果统计模板-->
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="icon" href="../../favicon.ico">
 <link href="http://cdn.bootcss.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">
    <title>BI决策支持系统</title>
  </head>

  <body style="padding: 20px 50px;">
    <ul class="list-inline">
      <li><strong>一次下单用户</strong>
        <ul>
        <li><a href="<?php echo $base_url?>/statics/one_order?site_id=1&stime=2015-02-12&etime=<?php echo date('Y-m-d');?>">大厨网</a></li>
        <li><a href="<?php echo $base_url?>/statics/one_order?site_id=2&stime=2015-02-12&etime=<?php echo date('Y-m-d');?>">大果网</a></li>
        </ul>
      </li>
      <li><strong>复购用户</strong>
        <ul>
        <li><a href="<?php echo $base_url?>/statics/again_order?site_id=1&stime=2015-02-12&etime=<?php echo date('Y-m-d');?>">大厨网</a></li>
        <li><a href="<?php echo $base_url?>/statics/again_order?site_id=2&stime=2015-02-12&etime=<?php echo date('Y-m-d');?>">大果网</a></li>
        </ul>
      </li>
      <li><b>注册未下单用户</b>
        <ul>
        <li><a href="<?php echo $base_url?>/statics/not_order?site_id=1">大厨网</a></li>
        <li><a href="<?php echo $base_url?>/statics/not_order?site_id=2">大果网</a></li>
        </ul>
      </li>
    </ul>
    
    <table class="table table-bordered">
       <tr>
         <th>门店名称</th>
         <th>门店地址</th>
         <th>客户姓名</th>
        <th>客户电话</th>
        <th>注册时间</th>
        <th>相关BD姓名</th>
        <th>相关BD电话</th>
      </tr>
      <?php foreach($data as $value){?>
      <tr>
      <td> <?php echo isset($value['shop_name']) ? $value['shop_name'] : ''?></td>
      <td> <?php echo isset($value['address']) ? $value['address'] : ''?></td>
      <td> <?php echo isset($value['name']) ? $value['name']: '' ?></td>
      <td> <?php echo isset($value['mobile']) ? $value['mobile'] : ''?></td>
      <td> <?php echo date('Y-m-d', $value['created_time'])?></td>
      <td> <?php echo isset($value['DB_name']) ? $value['DB_name'] : ''?></td>
      <td> <?php echo isset($value['DB_mobile']) ? $value['DB_mobile']:''?></td>
      </tr>
      <?php }?>
    </table>
    
  </body>
  </html>