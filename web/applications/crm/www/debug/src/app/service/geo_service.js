'use strict';
angular
.module('dachuwang')
.factory('geo',['$q', function($q) {
  var getGeo = function() {
    var geoInfo = {lat: '', lng: ''};
    var defered = $q.defer();
    if(navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(function (position) {
        geoInfo.lat = position.coords.latitude;
        geoInfo.lng = position.coords.longitude;
        defered.resolve(geoInfo);
      }, function(err) {
        defered.reject('ERROR('+ err.code+'):'+err.message);
      }, {
        enableHighAccuracy : true,
        timeout : 10000, //获取GEO信息超时时间
        maximumAge : 0
      });
    } else {
      defered.reject('未开启GPS定位，请先打开手机的GPS定位功能');
    }
    return defered.promise;
  }
  return {
    info: getGeo
  }
}]);
