'use strict';

//吸底
angular
.module('dachuwang')
.directive('fixedBottom', ['$document','$window', function($doc ,$win) {
  return {
    restrict : 'A',
    scope : true ,
    link: function(scope, ele, attr) {

      var win = angular.element($win);
      var body = angular.element($doc)[0].body;
      var rect = ele[0].getBoundingClientRect();
      rect.offsetTop = ele[0].offsetTop
      //  元素是否在可视区域
      var isVisble = function(){

        if(win[0].parent.innerHeight < rect.offsetTop &&  win[0].pageYOffset + win[0].parent.innerHeight < rect.offsetTop ||  win[0].pageYOffset >( rect.top + rect.height)) {
          return true;
        }else{
          return false;
        }

      }

      var toFixed = function(){

        var result = isVisble();
        if(result){
          ele.addClass('fixed-bottom')
          return ;
        }
          ele.removeClass('fixed-bottom')
      }

      toFixed();

      win.on('scroll' , toFixed)
    }
  };
}])



