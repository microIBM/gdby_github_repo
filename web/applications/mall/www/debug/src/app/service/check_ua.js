'use strict';

// 检查浏览器 @febobo
angular
.module('dachuwang')
.factory('checkUa',[ 'daChuDialog' , function(daChuDialog) {

  var dialog = daChuDialog ;
  var ua = navigator.userAgent.toLowerCase();
  var Sys = {} ;
  var version;
  var checkUa = function(){
    var version = ua.match(/chrome\/([\d.]+)/);
    if(!version){
      return false;
    }
    return true ;
  }
  return {
    checkUa : checkUa
  }
}])
