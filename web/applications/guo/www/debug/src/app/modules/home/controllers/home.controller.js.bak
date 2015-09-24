'use strict';


angular
.module('dachuwang')
.controller('homeController', ['$scope', '$state' ,'$rootScope' ,'$http','$window','locationService','notifyService','categoryService','cateObj','daChuLocal', 'advService', 'userAuthService','req', 'Lightbox' ,'posService', function($scope, $state ,$rootScope  , $http, $window, locationService, notifyService, categoryService, cateObj,daChuLocal, advService ,userAuthService, req , Lightbox ,posService) {


  $scope.$state = $state;
  $scope.localInfo = posService.info();
  $scope.isLogined = userAuthService.isLogined();
  // 命名空间
  var DC = $scope.DC = {};

  // 展示对应2级菜单
  DC.active_list = function(data){
    DC.data = data ;
  }

  DC.product = function(cateId){
    $rootScope.$emit('cateId' , cateId);
  }

  //搜索
  DC.search = function(data){
    $state.go('page.search' , {searchVal : DC.search_name}, {inherit : true})
  }
  // 取推荐位数据
  DC.cateArr = daChuLocal.get('cateArr');
  if(DC.cateArr && DC.cateArr.recommends &&  DC.cateArr.recommends.length){

      DC.recommends = DC.cateArr.recommends;
   // DC.recommends = DC.cateArr.recommends[0].products;
   // DC.first_recommend = DC.recommends.splice(0,1)[0];
  }
  // 如果图片幻灯还在先关闭
  if(Lightbox.modalInstance){
    Lightbox.closeModal() ;
  }
  //如果token不存在就强制退出
  if(!daChuLocal.get('token')){
    req.getdata('customer/logout', 'POST', function() {
      daChuLocal.remove('packaged_cate');
      daChuLocal.remove('coupon_active');
    });
  }
  $scope.category = cateObj;


  // 默认2级菜单
  DC.data = $scope.category.length ? $scope.category[0].cate_child : null;
  $scope.showAdv = false;

  //$scope.isLogined = userAuthService.isLogined();
  advService.getAds(1).then(function(data) {
    $scope.slides = [];
    if(parseInt(data.status) === 0) {
      angular.forEach(data.list, function(v) {
        var d = {
          sref : v.link_url,
          url : v.pic_url
        };
        $scope.slides.push(d);
      });
      if($scope.slides.length) {
        $scope.showAdv = true;
      }
    }
  }, function(msg) {
    $scope.slides = [];
  });
}]);
