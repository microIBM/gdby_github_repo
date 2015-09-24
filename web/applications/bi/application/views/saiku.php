<?php include APPPATH."views/shared/header.php"?>
<link href="../../resource/css/saiku.css">
<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
  <h1 class="page-header">
    <?php if($site_id==1):?>大厨网 · 品类分析<?php endif;?>
    <?php if($site_id==2):?>大果网 · 品类分析<?php endif;?>
  </h1>
      <iframe id="saiku_iframe" src="<?php echo $base_url;?>/saiku/login?site_id=<?php echo $site_id;?>&city_id=<?php echo $city_id;?>" width="100%" height="800px" frameborder="0" ></iframe>
</div>
<script src="../../resource/js/saiku.js" ></script>
<?php include APPPATH."views/shared/footer.php" ?>
