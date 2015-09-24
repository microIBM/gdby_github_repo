$(function(){
  $('#toggle-btn').click(function (){
    $('#mobile-nav').toggleClass('mobile');
  })
  $('#mobile-nav li').click(function(){
    $('.mobile').removeClass('mobile')
  })
  $(window).bind('scroll', function(event){
    $('.prolist li').each(function(){

      if($(window).height() + $(window).scrollTop() >= $(this).offset().top + $(window).height()/2){
        $(this).addClass('pulse')
      }else{
        $(this).removeClass('pulse')
      }
    })
  });

  if($(window).width() < 480){
    $(window).bind('scroll', function(event){
      $('.dachu_icon li').each(function(){

        if($(window).height() + $(window).scrollTop() >= $(this).offset().top + $(window).height()/2){
          $(this).addClass('cur')
        }else{
          $(this).removeClass('cur')
        }
      })
    });
  }
  jQuery(".join_banner").slide({mainCell:".joinus_banner ul",autoPlay:true});
  $('#mobile-nav li:last-child').click(function(){
    window.location.href = 'index.html#callus';
  })
  //loading 用locaStorage缓存
  var hour;
  function loading(){
    var date = new Date();
    hours = date.getHours();
  }
  loading();
  if(window.localStorage.getItem('time')){
    $('.loading').hide();
    $('.index_banner').addClass('no-load');
  }else{
    var date = new Date();
    var hour = date.getHours();
    window.localStorage.setItem('time', hour);
  }
  var locatime = window.localStorage.getItem('time');
  if((hours - parseInt(locatime)) > 1){
    window.localStorage.removeItem('time');
    $('.loading').show();
    $('.index_banner').removeClass('no-load')
  }
})
