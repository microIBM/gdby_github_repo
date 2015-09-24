angular
.module('dachuwang')
.factory('productService', ['$http','$q','categoryService', 'rpc', 'posService', function($http, $q, categoryService, rpc, posService) {
  var getProducts = function(upid, page , url) {
    var defered = $q.defer();
    if(isNaN(page) || page<=0) {
      return $q.derfer().reject('Error page param');
    }
    var id = upid,
    location = posService.info(),
    locationId = location.id;
    if(url){
      rpc.load( url, 'POST', {searchVal:id, currentPage : page, locationId : locationId}).then(function(msg) {
        defered.resolve(msg);
      });
    }else{
      rpc.load('product/lists', 'POST', {upid: id, currentPage : page, locationId : locationId}).then(function(msg) {
        defered.resolve(msg);
      });
    }
    return defered.promise;

  }
  return {
    getProducts : getProducts

  };
}]);
