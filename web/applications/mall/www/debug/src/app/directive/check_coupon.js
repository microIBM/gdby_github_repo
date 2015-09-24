'use strict';

//确认订单优惠劵 支付方式 收货地址选择 @febobo
angular
.module('dachuwang')
.directive('checkActive', ['$document','$window', function($document ,$window) {
  return {
    restrict : 'A',
    scope : {
      checkCoupon : '&',
      clearCoupon : '&',
      checkPay : '&',
      clearPay : '&',
      checkAddress : '&',
      clearAddress : '&'
    },
    link: function(scope, ele, attr) {

      // 收货地址选择
      if(attr.checkActive == 'checkAddress'){
        ele.on('click' , function(e){
          setClass(ele , 'activeAddress');
        })
      };

      // 优惠劵选择
      if(attr.checkActive == 'checkCoupon'){
        ele.on('click' , function(e){
          setClass(ele , 'activeCoupon');
        })
      };

      // 支付方式方式
      if(attr.checkActive == 'checkPay'){
        ele.on('click' , function(e){
          setClass(ele , 'activePay');
        })
      };

      var setClass = function(ele , _class){
        if(ele.hasClass(_class)){
          ele.removeClass(_class);
          if(attr.checkActive == 'checkCoupon') scope.clearCoupon();
          if(attr.checkActive == 'checkPay') scope.clearPay();
          if(attr.checkActive == 'checkAddress') scope.clearAddress();
        }else{
          var siblings = angular.element('.' + _class);
          ele.addClass(_class);
          if(attr.checkActive == 'checkCoupon') scope.checkCoupon();
          if(attr.checkActive == 'checkPay') scope.checkPay();
          if(attr.checkActive == 'checkAddress') scope.checkAddress();
          //  干掉其它兄弟的当前类
          angular.forEach(siblings , function(v , k){
            if(k != siblings.length - 2){
              angular.element(v).removeClass(_class);
            }
          })
        }
      }

    }
  };
}])



