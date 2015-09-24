'use strict';

angular
.module('dachuwang')
.controller('homeController', ['$scope', '$state' ,'$rootScope' ,'$http','$window','locationService','categoryService','cateObj','daChuLocal', 'advService', 'req' , 'Lightbox' , 'posService' ,'userAuthService' , 'rpc' ,function($scope, $state ,$rootScope, $http, $window, locationService,  categoryService, cateObj,daChuLocal, advService , req , Lightbox ,posService ,userAuthService , rpc) {



  $scope.$state = $state;
  $scope.localInfo = posService.info();
  $scope.isLogined = userAuthService.isLogined();
  // 命名空间
  var DC = $scope.DC = {};

  DC.imgLoad = function(){
      $scope.$apply(function() {
        $scope.showloading = true;
      });
  }

  // 获取一级分类
  DC.data = daChuLocal.get('cateArr');
  if(DC.data){
    DC.navList = DC.data.list;
    DC.secondList = DC.data.list.second;
    angular.forEach(DC.navList.top,function(v){
      v.itemChild = [];
      angular.forEach(DC.secondList,function(m,value){
        if(v.id == value){
          v.itemChild.push(m)
        }
      })
    })
  }

  // 展示对应2级菜单
  DC.active_list = function(data){
    DC.data = data ;
  }

  DC.product = function(cateId){
    $rootScope.$emit('cateId' , cateId);
  }

  $scope.productList = function(cateId){
    $state.go('page.list',{cateId:cateId})
  }

  //搜索
  DC.search = function(data){
    $state.go('page.search' , {searchVal : DC.search_name}, {inherit : true})
  }

  // 取推荐位数据
  DC.cateArr = daChuLocal.get('cateArr');
  if(DC.cateArr && DC.cateArr.recommends &&  DC.cateArr.recommends.length){
    DC.recommends = DC.cateArr.recommends;
    angular.forEach(DC.recommends , function(v){
      if(v && v.products){
        angular.forEach(v.products , function(pv){
          // 把包装规格抽出来
          if(pv.spec){
            angular.forEach(pv.spec , function(productSpec){
              if(productSpec.name == '包装规格' || productSpec.name == '规格'){
                pv.productSpec = productSpec;
              }
            })
          }
        })
      }
    })
  }

  // 关注
  DC.follow = function(item){
    if(!userAuthService.isLogined()){
      $state.go('page.login');
      return ;
    }
    if(!item){
      return ;
    }
    var id = item.id ;
    var status ;
    if(item.follow_status  == 0){
      status = 1;
      item.follow_status = 1;
    }else{
      status = 0;
      item.follow_status = 0;
    }
    rpc.load('follow_with_interest/update_or_insert' , 'POST' , {product_id : id , status : status}).then(function(data){

    })
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
  $scope.myinterval = 3000;
  advService.getAds(1).then(function(data) {
    DC.advList = data;
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
