<div class="bi-box" style="display: table;background-color: #f9f9f9; padding: 10px; border: 1px solid #205081;">
  <h1 style="font-size: 20px; background-color: #205081; color: white; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; margin: -10px 0px 0px; padding: 5px;">大厨网邮件报告</h1>
  <h2 style="font-size: 16px; margin-bottom: 30px; margin-top: 10px;"><?php if($topic) echo $topic ?></h2>
  <?php foreach($table as $value): ?>
  <div class="bi-table" style="margin-top: 20px;">
    <h3 style="font-size: 14px; margin-bottom: 5px;">
      <span style="padding: 5px 20px; border: 1px solid #205081;"><?php if(isset($value['title'])) echo $value['title'] ?></span>
    </h3>
    <table style="width: 100%; border-spacing: 0px; border: 1px solid #205081;">
      <?php if(isset($value['header'])): ?>
      <thead>
        <tr>
          <?php foreach($value['header'] as $header): ?>
          <th style="text-align: left; background-color: #205081; color: white; font-size: 13px; padding: 10px;" align="left" bgcolor="#205081"><?php echo $header ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <?php endif; ?>
      <?php if(isset($value['content'])): ?>
      <tbody style="font-size: 12px;">
        <?php
        $count = 0;
        foreach ($value['content'] as $row) :
        $count++;
        ?>
        <?php if(fmod($count, 2) == 0) {
            $bgcolor = 'background-color: #DEEBFB;';
        } else {
            $bgcolor = '';
        } ?>
        <tr style="<?php echo $bgcolor ?>">
          <?php foreach ($row as $item) :?>
          <td style="padding: 10px;"><?php echo $item ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <?php endif; ?>
    </table>
    <?php if(isset($value['desc'])): ?>
    <div class="bi-table-desc" style="border-left-width: 1px; border-left-color: #205081; border-left-style: solid; border-right-style: solid; border-right-color: #205081; border-right-width: 1px; border-bottom-color: #205081; border-bottom-width: 1px; border-bottom-style: solid; padding: 10px;">
      <p style="margin-top: 0px; margin-bottom: 0px; font-size: 14px;">表格说明</p>
      <?php foreach($value['desc'] as $desc): ?>
      <p style="font-size: 13px; padding-top: 5px; margin-top: 0px; margin-bottom: 0px;">※ <?php echo $desc ?></p>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
  <?php foreach($topic_desc as $desc): ?>
    <p style="font-size: 14px; padding: 5px; margin-top: 20px; margin-bottom: 10px; border-left: solid 3px #205081;"><?php echo $desc ?></p>
  <?php endforeach; ?>
</div>
