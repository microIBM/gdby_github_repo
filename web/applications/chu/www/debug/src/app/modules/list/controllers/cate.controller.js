'use strict';

angular
.module('dachuwang')
.controller('cateController', ['$scope','$rootScope' , '$state' ,'$stateParams','$http','$window','locationService','notifyService','categoryService','cateObj','daChuLocal', 'advService', 'req' , 'Lightbox' , 'posService' , 'userAuthService' , function($scope,$rootScope, $state ,$stateParams ,$http, $window, locationService, notifyService, categoryService, cateObj,daChuLocal, advService , req , Lightbox ,posService ,userAuthService) {

  $rootScope.init_cateId =cateObj.length && cateObj[0].cate_child.length ? cateObj[0].cate_child[0].id : 12 ;
  $scope.$state = $state;
  $scope.localInfo = posService.info();
  $scope.isLogined = userAuthService.isLogined();


  // 命名空间
  var DC = $scope.DC = {};

  //默认 2级分类高亮
  DC.state = $rootScope.init_cateId;
  // 展示对应2级菜单
  DC.active_list = function(data){
    DC.data = data.cate_child;
    var cateId = DC.data.length ? DC.data[0].id : null ;
    DC.isActive() ? false : DC.active() ;
    // 一级分类高亮
    angular.forEach($scope.category , function(v){
      if(data.cate_parent_id == v.cate_parent_id){
        v.activeClass = true;
      }else{
        v.activeClass = false;
      }
    })
    //切换 2级分类高亮
    DC.state = cateId;
    $rootScope.$emit('cateId' , cateId);
  }

  // 检查是否用户已查看过二级分类
  DC.isActive = function(){
    var isActive;
    angular.forEach(DC.data , function(v){
      if(v.activeClass){
        isActive = true;
      }
    })
    if(isActive){
      return true;
    }else{
      return false;
    }
  }

  DC.product = function(cateId){
    if(!cateId) return ;
    angular.forEach(DC.data , function(v){
      if(v.id == cateId){
        v.activeClass = true;
      }else{
        v.activeClass = false;
      }
    })
    $rootScope.$emit('cateId' , cateId);
  }

  //搜索
  DC.search = function(data){
    $state.go('page.search' , {searchVal : DC.search_name}, {inherit : true})
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

  // 找出一级分类下的二级菜单
  DC.selectChildren = function(){
    angular.forEach($scope.category , function(v ,k){
      angular.forEach($scope.category[k].cate_child , function(sv , sk){
        if(sv.id == $stateParams.cateId){
          DC.data = $scope.category[k].cate_child;
          DC.cate_id = v.cate_parent_id;
        }
      })
      if(DC.cate_id && DC.cate_id == v.cate_parent_id){
        v.activeClass = true;
      }else{
        v.activeClass = false;
      }
    })
  }

  // 默认2级菜单
  if($stateParams.cateId){
    DC.state = $stateParams.cateId;
    DC.selectChildren();
  }else{
    DC.data = $scope.category[0].cate_child;
    DC.cate_id = $scope.category[0].cate_parent_id;
  }

  // 为第一项加上高亮
  DC.active = function(){
    DC.data && DC.data.length ? DC.data[0].activeClass = true : false ;
  }

  DC.active();
  $scope.showAdv = false;
  advService.getAds(1).then(function(data) {
    $scope.slides = [];
    if(parseInt(data.status) === 0) {
      angular.forEach(data.list, function(v) {

        // 如果link_url 不存在转到首页，避免报错
        if(!v.link_url){
          v.link_url = 'page.home'
        }
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
