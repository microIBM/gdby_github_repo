'use strict';

//确认订单时间选择 @febobo
angular
.module('dachuwang')
.directive('checkDate', ['$document','$window', function($document ,$window) {
  return {
    restrict : 'A',
    scope : {
      checkEnd : '&',
      clearDate : '&'
    },
    link: function(scope, ele, attr) {

      ele.on('click' , function(e){
        setClass(ele.children());
      })

      var setClass = function(ele){
        if(ele.hasClass('active')){
          ele.removeClass('active').text('可选');
          scope.clearDate();
        }else{
          var siblings = angular.element('.active');
          ele.addClass('active').text('已选择');

          scope.checkEnd();
          //  干掉其它兄弟的当前类
          angular.forEach(siblings , function(v , k){

            if(k != siblings.length - 2){
              angular.element(v).removeClass('active').text('可选');
            }
          })
        }
      }

    }
  };
}])



