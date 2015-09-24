'use strict';

//商品关注 @febobo
angular
.module('dachuwang')
.directive('follow', ['$document','$window', function($document ,$window) {
  return {
    restrict : 'A',
    link: function(scope, ele, attr) {

      var $ = function(ele){
        return angular.element(ele);
      }
      if(attr.followTab == 'list'){
        var parent = ele.parent();
        parent.on('mouseover' , function(e){
          var img = parent.find('img')[1];
          $(img).addClass('scaleIn');
          parent.addClass('active')
          ele.show();
          e.stopPropagation();
        })
        parent.on('mouseout' , function(e){
          var img = parent.find('img')[1];
          parent.removeClass('active')
          $(img).removeClass('scaleIn');
          ele.hide();
          e.stopPropagation();
        })
      }

      var setClass = function(){
        if(ele.hasClass('cate-nofollow')){
          ele.removeClass('cate-nofollow').text('关注');
        }else{
          ele.addClass('cate-nofollow').text('已关注');
        }
      }

      ele.on('click' , function(){
        setClass();
      })

      // 页面进来初始化关注状态
      if(attr.follow == 1){
        ele.addClass('cate-nofollow').text('已关注');
      }else{
        ele.removeClass('cate-nofollow').text('关注');
      }
    }
  };
}])



