'use strict';

// 预定设置class
angular
.module('dachuwang')
.directive('activeClass', ['$document','$window', '$timeout' ,'cartlist',function($document ,$window,$timeout , cartlist) {
  var win = angular.element($window);

  var doc = angular.element($document);
  return {
    restrict : 'A',
    scope : {
      keyCode : '&'
    },
    link: function(scope, ele, attr) {

      //  自动聚焦
      if(attr.activeClass == "autoFocus"){
        $timeout(function(){
          ele.focus;
        },200)
        ele.on('keydown' , function(e){
          if(e.keyCode == 13){
            scope.keyCode();
          }
        })
      }
      // 渐隐消失
      if(attr.activeClass == "mask"){
        ele.on('touchstart' , function(){
          ele.addClass('fadeOut')
        })
        if(!ele.hasClass('fadeOut')){
          $timeout(function(){
            ele.addClass('fadeOut')
          },5000)
        }
      }

      // 去掉头自适应高
      if(attr.activeClass == 'autoHeight'){
        var winH = win[0].innerHeight;
        var h =  winH - 53 + 'px' ;
        ele.css({
          height : h ,
          overflowY : 'scroll',
          overflowX : 'hidden'
        })
        // 去掉头尾加一级分类导航自适应高
      }else if(attr.activeClass == 'autoList'){
        var winH = win[0].innerHeight;
        var h =  winH - 137 + 'px' ;
        ele.css({
          height : h ,
          overflowY : 'scroll',
          overflowX : 'hidden'
        })
        // 去掉头尾自适应高
      }else if(attr.activeClass == 'autoCart'){
        var winH = win[0].innerHeight;
        var h =  winH - 95 + 'px' ;
        ele.css({
          height : h ,
          overflowY : 'scroll',
          overflowX : 'hidden'
        })
      }
    }
  };
}])



