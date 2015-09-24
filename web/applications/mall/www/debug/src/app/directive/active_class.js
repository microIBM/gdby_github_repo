'use strict';

// 预定设置class
angular
.module('dachuwang')
.directive('activeClass', ['$state' ,'$document','$window', '$timeout' ,'cartlist',function( $state , $document ,$window,$timeout , cartlist) {
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
      // 首尾去空格
      var trim = function(str){
         if(typeof str != 'string'){
            return ;
         }
         var str = str.replace(/^\s+ | \s&/ , '');
         return str ;
      }

      //  enter 事件
      if(attr.activeClass == "keyCode"){
        ele.on('keydown' , function(e){
          if(e.keyCode == 13){
             var searchName = ele.val();
             if(searchName && trim(searchName) != ''){
                $state.go('page.search' , {searchVal : searchName}, {inherit : true})
             };
          }
        })
      }
      if(attr.activeClass == "couponNav"){
        ele.on('click' , function(){
          setClass('active')
        })
      }
    }
  };
}])



