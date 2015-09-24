'use strict';

// 预定设置class
angular
.module('dachuwang')
.directive('activeClass', ['$document','$window', '$timeout' ,'cartlist',function($document ,$window,$timeout , cartlist) {
  var win = angular.element($window);

  var doc = angular.element($document);
  return {
    restrict : 'A',
    link: function($scope, ele, attr) {
      var setClass = function(_class){

          var active = ele.parent().find('.' + _class);

          // 如果active 类先存在， 先干掉 ， 为当前点击的这个加上
          if(active){
            active.removeClass(_class);
          }
          ele.addClass(_class)

      }

      //  自动聚焦
      if(attr.activeClass == "autoFocus"){
        $timeout(function(){
          ele.focus();
        },200)
        ele.on('keydown' , function(e){
          if(e.keyCode == 13){
              $scope.getProduct($scope.DC.search_name, 1 , 'product/search');
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
      if(attr.activeClass == "slide_list"){

        ele.on('click' , function(){
          setClass('greenB_textF')
        })
      }else if(attr.activeClass == 'nav_list'){

        ele.on('click' , function(){
          setClass('br_b_2')
        })

        // 去掉头自适应高
      }else if(attr.activeClass == 'autoHeight'){
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



