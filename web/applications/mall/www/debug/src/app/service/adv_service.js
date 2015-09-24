'use strict';

angular.module('dachuwang')
.factory('advService', ['$q', 'rpc', 'daChuLocal', function($q, rpc, daChuLocal) {
  // 获取广告
  var getAds = function(pos_id) {
    var defered = $q.defer();
    // 检测当前城市
    var localInfo = daChuLocal.get('currentLocation'),
    localId = localInfo ? localInfo.id : 804;

    rpc.load('ads/lists', 'POST', {pos_id: 1, locationId: localId}).then(function(data) {
      defered.resolve(data);
    }, function(msg) {
      defered.resolve(false);
    })
    return defered.promise;
  }

  var getAdsDetail = function(id) {

    var detailDefered = $q.defer();
    rpc.load('ads/info', 'POST', {id : id}).then(function(promise) {

      detailDefered.resolve(promise);
    }, function(msg) {

      defered.resolve(false);
    })
    return detailDefered.promise;
  }
  return {
    getAds : getAds,
    detail : getAdsDetail
  }
}]);
