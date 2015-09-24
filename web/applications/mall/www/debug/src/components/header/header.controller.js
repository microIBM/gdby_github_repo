'use strict';

angular
.module('dachuwang')
.controller('headerController', ['$rootScope'  ,'$scope','daChuLocal','daChuTimer' , 'userService', '$state', 'cartlist' ,'posService', 'userAuthService', 'rpc','categoryService' , function( $rootScope ,$scope,daChuLocal, daChuTimer , userService, $state,cartlist ,posService, userAuthService , rpc , categoryService) {

  var DC = $scope.DC = {};

  //下拉菜单
  $scope.status = {
    isopen:false
  }


  // 监听购物车变化
  $rootScope.$on('cart_sum' , function(e , cartSumChange){
    DC.cartlist  = cartSumChange ;
  })

  // 监听用户登陆变化
  $rootScope.$on('user_info' , function(e , userInfo){
    DC.userInfo  = userInfo ;
    DC.userCity = {};

    // 刷新城市
    if(DC.userInfo.name){
      DC.isLogin = true ;
      DC.userCity.name = DC.userInfo.city_name;
      DC.userCity.id = DC.userInfo.city_id;
      $scope.localInfo = DC.userCity;
    }else{
      DC.isLogin = false ;
    }
  })

  DC.cartlist = cartlist.getInfo();
  // 检查登陆状态 取缓存用户信息
  if(userAuthService.isLogined()) {
    DC.isLogin = true ;
    DC.userInfo = daChuLocal.get('userInfo');
  }else{
    DC.isLogin = false ;
  }

  // 取消
  DC.reset = function(){
    DC.search_name = '';
  }
  DC.search = function(data , $event){
    // 如果输入跳到search页
    if(!data.$valid || !data.$dirty){
      return ;
    }
    $state.go('page.search' , {searchVal : DC.search_name}, {inherit : true})
  }

  var setDefault = function() {
    var data = daChuLocal.get('localCity')  || {};
    if(typeof data.list === 'undefined' || !daChuTimer.compare(data.currentTime)) {
      // 检测缓存
      rpc.load('/location/city', 'GET').then(function(data) {
        $scope.cities = data.list;
        var localCity = {currentTime: daChuTimer.getNow(), list: data.list};
        daChuLocal.set('localCity', localCity);
      });
    } else {
      $scope.cities = data.list;
    }
    // 默认一样的则不能点击标红
  }

  setDefault();
  //登录退出
  DC.login_out = function(){
    userService.login_out();
  }


  DC.uaTipsShow = function(){
    $rootScope.uaTips = false ;
  }
  // 二维码展开
  DC.erwei = false;
  DC.erweiIn = function(){
    DC.erwei = true;
  }
  DC.erweiOut = function(){
    DC.erwei = false;
  }

  DC.navList = daChuLocal.get('cateArr').list;
  DC.secondList = DC.navList.second;
  angular.forEach(DC.navList.top,function(v){
    v.itemChild = [];
    angular.forEach(DC.secondList,function(m,value){
      if(v.id == value){
        v.itemChild.push(m)
      }
    })
  })
  $scope.productList = function(cateId){
    $state.go('page.list',{cateId:cateId})
  }

  // 选择城市
  DC.citySelect = function(item) {
    var index = $scope.cities.indexOf(item);
    // 当前位置
    // 若登录不允许出现选择
    if(!userAuthService.isLogined()) {
      daChuLocal.remove('cateArr');
      posService.setInfo($scope.cities[index].id,$scope.cities[index].name);
      daChuLocal.remove('packaged_cate');
      categoryService.getAll();
    } else {

      daChuLocal.remove('packaged_cate');
      alert("您的地理位置不能修改")
    }
    //$state.go('page.home');
    $state.reload();
  }

  // 尾部配置
  DC.detail_config = [
    { classname: 'youzhi', name :"优质货源"},
    { classname: 'ruqi', name :"如期送达"},
    { classname: 'zhangdan', name :"电子账单服务"},
    { classname: 'jushou', name :"现场无理由拒收"}
  ]
  $scope.$state = $state;
  $scope.localInfo = posService.info();

}]);
