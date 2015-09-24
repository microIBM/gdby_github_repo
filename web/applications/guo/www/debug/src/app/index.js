'use strict';

angular
.module('dachuwang', [
//  'ngAnimate',
  'ngCookies',
  'ngTouch',
  'ngSanitize',
  'ui.router',
  'bootstrapLightbox',
  'ui.bootstrap'
])
.run(function($rootScope, $state , $templateCache) {
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

  $templateCache.put('lightbox.html',
                     "<div class=modal-body ng-swipe-left=Lightbox.nextImage() ng-swipe-right=Lightbox.prevImage()><div class='close_m_btn' ng-click='Lightbox.closeModal()'><span class='glyphicon glyphicon-remove'></span></div><div class=lightbox-image-container><span class='glyphicon glyphicon-menu-left' ng-show='lightBox_len > 1'  ng-click=Lightbox.nextImage()></span><span class='glyphicon glyphicon-menu-right'   ng-show='lightBox_len > 1' ng-click=Lightbox.prevImage()></span><div class=lightbox-image-caption><span>{{Lightbox.imageCaption}}</span></div><img lightbox-src={{Lightbox.imageUrl}} class='' alt=\"\"></div></div></div>"
                    )
});
