<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>BI决策支持系统</title>

    <!-- Bootstrap core CSS -->
    <link href="http://cdn.bootcss.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="<?php echo APPPATH ?>views/shared/css/login.css" rel="stylesheet">
  </head>

  <body style="background-image: url(<?php echo $base_url ?>/resource/img/login_bg.png);">
    <div class="login-header" style="background-image: url(<?php echo $base_url ?>/resource/img/login_header.png);"></div>
    <div class="login-logo" style="background-image: url(<?php echo $base_url ?>/resource/img/login_logo.png);"></div>
    <div class="login-divider" style="background-image: url(<?php echo $base_url ?>/resource/img/login_divider.png);"></div>
    <div class="login-bi" style="background-image: url(<?php echo $base_url ?>/resource/img/login_bi.png);"></div>

  <div class="login-box blood">
    <img class="login-title" src="<?php echo $base_url ?>/resource/img/login_title.png">
    <form class="form-signin" action="<?php echo $base_url;?>/user/login" type="post">

      <div class="alert alert-danger hide" id="wrong" role="alert"></div>

      <div class="login-name">
        <input type="text" id="inputText" class="form-control" name="mobile" placeholder="手机号" required autofocus >
        <div class="img-box"><img src="<?php echo $base_url ?>/resource/img/login_mobile.png"></div>
      </div>
      <div class="login-password">
        <input type="password" id="inputPassword" class="form-control" name="password" placeholder="密码" required >
        <div class="img-box"><img src="<?php echo $base_url ?>/resource/img/login_locked.png"></div>
      </div>
      <div class="checkbox">
        <label>
          <input type="checkbox" name="remember-me"  value="remember-me">记住一周
        </label>
      </div>

      <button id="submit"  class="btn btn-lg btn-login" type="button" >登录</button>
    </form>
  </div>
  <div class="login-footer" style="background-image: url(<?php echo $base_url ?>/resource/img/login_footer.png);">
    <p>Copyright © 2004-2015 北京大厨网络科技有限公司|京ICP备15010B35号</p>
  </div>

    <script src="http://cdn.bootcss.com/jquery/1.11.2/jquery.min.js"></script>
    <script src="http://cdn.bootcss.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
    <script type="text/javascript">
        var base_url = "<?php echo $base_url; ?>";
        $(function(){
            $("#submit").click(function(){
                if($("input[name=mobile]").val() == '' || $("input[name=password]").val() == ''){
                    $("#wrong").html('请输入手机号或密码');
                    $("#wrong").removeClass("hide");
                    return false;
                }else{
                    $.ajax({
                        'url' : base_url+'/user/login',
                        'type' : "post",
                        'dataType' : 'json',
                        'data' : {
                            'mobile' : $("input[name=mobile]").val(),
                            'password' : $("input[name=password]").val(),
                            'remember-me' : $("input[name=remember-me]").val()
                        },
                        'success' : function(data){
                            if(data && data.status == 0){
                                location = base_url;
                            }else{
                                $("#wrong").html(data.msg);
                                $("#wrong").removeClass("hide");
                            }
                        }
                    });
                }
            });
        });
    </script>
  </body>
</html>
