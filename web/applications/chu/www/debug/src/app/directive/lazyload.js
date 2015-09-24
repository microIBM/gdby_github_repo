'use strict';

//图片懒加载
angular
.module('dachuwang')
.directive('lazySrc', ['$document','$window','cartlist', function($document ,$window,cartlist) {
  var win = angular.element($window);

  var doc = angular.element($document);

  var elements = (function(){

    var _uid = 0;
    var _list = {};
    return {
      push : function(_data){
        _list[_uid ++ ] = _data;
        setTimeout(function(){
          checkImage(_data);
        })
      },
      del: function(key){
        _list[key] && delete _list[key];
      },
      size : function(){
        return Object.keys(_list).length ;

      },
      get : function(){
        return _list;
      }
    }
  })()

  var isVisible = function(elem){

    var rect = elem[0].getBoundingClientRect();
    var ret = true;
    if(rect.height > 0 && rect.width >0){
      var x = rect.top > 0 && (rect.top + rect.height / 3) < Math.max(doc.documentElement.clientHeight , win.innerHeight || 0);
      var y = rect.left > 0 && (rect.left + rect.width /3) < Math.max(doc.documentElement.clientHeight , win.innerHeight || 0);
      ret = x && y;
    }
    return ret ;
  }

  var checkImage = function(evt , i ,item){

    if( i >= 0 && item ){

      return isVisible(item.elem) ? item.load(i) : false ;
    }else if( elements.size() == 0){
      win.off('scroll' , checkImage);
      win.off('resize' , checkImage);
    } else{

      angular.forEach(elements.get() , function(item , key){
        isVisible(item.elem) && item.load(key);
      })
    }
  }
  var initLazyload = function(){
    if(isLazyding === false){

      isLazyding = true;
      win.on('scroll' , checkImage);
      win.on('resize' , checkImage);
    }
  }
  return {
    restrict : 'A',
    scope : {},
    link: function(scope, ele, attr) {
      ele[0].style.cssText && ele.data('cssText' , ele[0].style.cssText);

      ele.css({
       'min-width' : '1px ',
       'min-height' : '1px'
      })
      elements.push({
        elem : ele,
        load : function(key){

          ele.data('cssText') && (ele[0].style.cssText = ele.data('cssText'));

          ele.removeClass('ng-lazyload');
          ele.attr('src' , attr.lazySrc);
          key >=0 && elements.del(key);
          scope.$destroy();
          return true;
        }
      });

      initLazyload();
    }
  };
}])



