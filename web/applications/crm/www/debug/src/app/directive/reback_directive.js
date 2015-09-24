'use strict';
// 返回
angular
  .module('dachuwang')
  .directive('reback', ['$state', 'daChuDialog', function($state, dialog) {
    function goBack(backState, state) {
      if(!state) {
        return;
      }
      if(backState) {
        state.go(backState);
      } else {
        history.go(-1);
      }
    }
    return {
      link: function($scope, ele) {
        ele.bind('click', function() {
          var data = $state.current.data;
          if(data.showTips) {
            dialog.tips({
              bodyText: '返回将不保存本次编辑的内容',
              actionText: "确定",
              ok: function() {
                goBack(data.backState, $state);
              }
            });
          } else {
            goBack(data.backState, $state);
          }
        });
      }
    };
}]);
