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

  <body style="padding:10px 50px">
    
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
    
    <table class="table  table-bordered">
      <th>姓名</th>
      <th>店铺</th>
      <th>电话</th>
      <th>下单次数</th>
      <th>地址</th>
      <th>BD姓名</th>
      <th>BD电话</th>
      <?php foreach ($again_orders as $key => $value) :?>
        <tr>
          <td><?php echo isset($value['name']) ? $value['name'] : '';?></td>
          <td><?php echo isset($value['shop_name']) ? $value['shop_name'] : '';?></td>
          <td><?php echo isset($value['mobile'])?$value['mobile']:'';?></td>
          <td><?php echo isset($value['again_count'])?$value['again_count']:'';?></td>
          <td><?php echo isset($value['address'])?$value['address']:'';?></td>
          <td><?php echo isset($value['bd_name'])?$value['bd_name']:'';?></td>
          <td><?php echo isset($value['bd_mobile'])?$value['bd_mobile']:'';?></td>
          
        </tr>
      <?php endforeach;?>
    </table>
  </body>
  </html>