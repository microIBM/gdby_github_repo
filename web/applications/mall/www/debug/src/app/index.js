'use strict';

angular
.module('dachuwang', [
  // 'ngAnimate',
  'ngCookies',
  'ngTouch',
  'ngSanitize',
  'ui.router',
  'bootstrapLightbox',
  'ui.bootstrap',
  'angularFileUpload'
])
.run(function($rootScope, $state , $templateCache , checkUa , daChuDialog) {

  // 检查非chrome 浏览器 给与提示
  if(!checkUa.checkUa()){
    $rootScope.uaTips = true ;
  };

  $rootScope.$on('$stateChangeStart', function(event, toState, toParams, fromState, fromParams) {
    $rootScope.showLoading = true;
  });
  $rootScope.$on('$stateChangeSuccess', function(event, toState, toParams, fromState, fromParams) {
    $rootScope.showLoading = false;
    $rootScope.$emit('pageChanged', $state.current.data);
  });

  // 二级分类冒泡传递id给list controller 
  $rootScope.$on('cateId' , function(e , cateId){
     $rootScope.$broadcast('list_data' , cateId);
  })

  // 购物车状态改态传递 
  $rootScope.$on('cartChange' , function(e , cartChange){
     $rootScope.$broadcast('cart_data' , cartChange);
  })

  $rootScope.$on('cartSumChange' , function(e , cartSumChange){
     $rootScope.$broadcast('cart_sum' , cartSumChange);
  })
  // 用户登陆传播状态到  header controller 
  $rootScope.$on('userInfo' , function(e , userInfo){
     $rootScope.$broadcast('user_info' , userInfo);
  })

  $templateCache.put('lightbox.html',
                     "<div class=modal-body ng-swipe-left=Lightbox.nextImage() ng-swipe-right=Lightbox.prevImage()><div class='close_m_btn' ng-click='Lightbox.closeModal()'><span class='glyphicon glyphicon-remove'></span></div><div class=lightbox-image-container><span class='glyphicon glyphicon-menu-left' ng-show='lightBox_len > 1'  ng-click=Lightbox.nextImage()></span><span class='glyphicon glyphicon-menu-right'   ng-show='lightBox_len > 1' ng-click=Lightbox.prevImage()></span><div class=lightbox-image-caption><span>{{Lightbox.imageCaption}}</span></div><img lightbox-src={{Lightbox.imageUrl}} class='' alt=\"\"></div></div></div>"
                    )
});
