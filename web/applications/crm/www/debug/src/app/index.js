'use strict';

angular
.module('dachuwang', [
  'ngAnimate',
  'ngCookies',
  'ngTouch',
  'ngSanitize',
  'ui.router',
  'ui.bootstrap',
  'infinite-scroll',
  'bootstrapLightbox',
  'chart.js'
])
.run(function($rootScope, $state, $templateCache, Analysis) {
  var lastTime = null;
  $rootScope.$on('$stateChangeStart', function(event, toState, toParams, fromState, fromParams) {
    $rootScope.showLoading = true;
  });
  $rootScope.$on('$stateChangeSuccess', function(event, toState, toParams, fromState, fromParams) {
    var thisTime,duration,obj;
    $rootScope.showLoading = false;
    $rootScope.$broadcast('pageChanged', $state.current.data);
    thisTime = new Date().getTime();
    if(lastTime) {
      duration = thisTime - lastTime;
    }
    if(duration) {
      Analysis.send('页面访问记录',{"pageName":fromState.name}, duration);
    }
    lastTime = thisTime;
  });

 $templateCache.put('lightbox.html',"<div class=modal-body ng-swipe-left=Lightbox.nextImage() ng-swipe-right=Lightbox.prevImage()><div class='close_m_btn' ng-click='Lightbox.closeModal()'><span class='glyphicon glyphicon-remove'></span></div><div class=lightbox-image-container><span class='glyphicon glyphicon-menu-left' ng-show='imglength > 1'  ng-click=Lightbox.nextImage()></span><span class='glyphicon glyphicon-menu-right' ng-show='imglength > 1' ng-click=Lightbox.prevImage()></span><div class=lightbox-image-caption><span>{{    Lightbox.imageCaption}}</span></div><img lightbox-src={{Lightbox.imageUrl}}     class='' alt=\"\"></img></div></div>")
});

// 获取geo信息
window.geoData = {
  callback : null,
  setAddress : function(data) {
    if(typeof data === 'string') {
      data = JSON.parse(data);
    }
    if(this.callback) {
      this.callback(data);
    }
  }
};
window.callback_function = {};
