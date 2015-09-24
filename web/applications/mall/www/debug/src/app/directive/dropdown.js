'use strict';

//二级下拉菜单，鼠标经过展开收起效果
angular
.module('dachuwang')
.directive('dropDown', ['$document','$window', function($doc ,$win) {
  return {
    restrict : 'A',
    scope : {index:"=index"} ,
    link: function(scope, ele, attr) {

      ele.on('mouseenter' , function(){
        if(scope.index >3){
          ele.addClass('bottom')
        }
      })
    }
  };
}])



