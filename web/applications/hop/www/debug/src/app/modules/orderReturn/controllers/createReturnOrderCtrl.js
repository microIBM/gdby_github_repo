'use strict'
angular.module('hop').controller('createReturnOrderCtrl', ['$location', 'dialog', 'req', '$scope', '$cookieStore', '$state', 'appConfigure', function($location, dialog, req, $scope, $cookieStore, $state, appConfigure) {
  var common = {
    init: function() {
      this.param();
      this.request.getCreateInfo();
      this.scopeBind();
    },
    param: function() {
      $scope.returnContents = [];
      $scope.totalReturnPrice = 0;
      $scope.vm = {
        suborder_id: "",
        reason: "",
        content: "",
        deposit_bank: "",
        bank_number: "",
        account_holder: "",
        remark: "",
        deal_method: ""
      }
    },
    request:{
      getCreateInfo: function() {
        req.getdata('rejected/for_create', 'POST', function(json) {
            console.log(json);
            if (json.status == 0) {
              $scope.info = json.rejected_info;
            }else if(json.status === -2){
              dialog.tips({
                  actionText: '返回',
                  bodyText: json.msg,
                  ok: function() {
                      window.history.go(-1);
                  }
              },{ templateUrl: 'dialog.html'});
            }
        }, {
          "suborder_id": $state.params.id
        })
      },
      createReturnOrder: function() {
        var arrContents = []
        $scope.returnContents.filter(function(item) {
          arrContents.push({
            id: item.id,
            product_id:item.product_id,
            quantity: item.number
          });
        })

        if(arrContents.length==0){
            dialog.tips({
                bodyText: '你还没有选择退货商品!'
            })
            return;
        }

        var postData = {
          suborder_id: $state.params.id,
          reason: $scope.vm.reason.id,
          content: arrContents,
          deposit_bank: $scope.vm.deposit_bank,
          bank_number: $scope.vm.bank_number,
          account_holder: $scope.vm.account_holder,
          suggestion: $scope.vm.remark,
          deal_method: $scope.vm.deal_method.id
        };

        req.getdata('/rejected/create', 'POST', function(json) {
          if (json.status == 0) {
             $location.path("/orderReturn/index");
          }
        }, postData, true)
      }
    },
    scopeBind: function() {
      // 更新正在选退货商品的价格
      $scope.$on("updateTotalPrice", function(eve) {
        $scope.contentSelModel.totalPrice = $scope.contentSelModel.number * $scope.contentSelModel.actual_price;
      })

      // 计算总退款金额
      $scope.$on("finalReturnPrice", function(eve) {
        $scope.totalReturnPrice = 0;
        $scope.returnContents.filter(function(item) {
          $scope.totalReturnPrice += item.totalPrice;
        })
      })

      // 提示弹框
      $scope.dialogFun = function(number, callback) {
        dialog.tips({
          actionText: '确定',
          bodyText: '你已经超过了最大的添加数量:(' + number + ')',
          ok: function() {
            if (typeof callback === "function") {
              callback.apply()
            }
          }
        }, {
          templateUrl: 'dialog.html'
        });
      }

      $scope.validateExitFloat=function(){
          // 是否添加的商品中，有数量为小数的, 默认为就地报损
          $scope.returnContents.filter(function(item){
              if(item.number.indexOf('.')>-1){
                $scope.vm.deal_method = $scope.info.deal_methods[1];
                $scope.isExistFloat=true;
                return;
              }else{
                $scope.isExistFloat = false;
                $scope.vm.deal_method = null;
                return;
              }
          })
      }

      // 增加
      $scope.addContent = function(model) {
        // alert(parseFloat(model.number)>parseFloat(model.actual_quantity))
        // return;
        // console.log(model.number + "," + model.actual_quantity);
        if (model.number==undefined) {
        }else if (parseFloat(model.number) > parseFloat(model.actual_quantity)) {
          $scope.dialogFun(model.actual_quantity, null);
        } else {
          model = $scope.info.content.splice($scope.info.content.indexOf(model), 1)[0];
          $scope.contentSelModel = null;
          $scope.returnContents.push(model);
          // 验证是否有小数的
          $scope.validateExitFloat();
          
          $scope.$emit("finalReturnPrice");
        }
      }

      // 创建退货退款单
      $scope.create = function() {
        common.request.createReturnOrder();
      }

      // 减去
      $scope.reduceContent = function(model) {
        model = $scope.returnContents.splice($scope.returnContents.indexOf(model), 1);
        $scope.info.content.push(model[0]);

        $scope.validateExitFloat();
        $scope.$emit("finalReturnPrice");
      }

      // 监听筛选退货商品的变化
      $scope.$watch("contentSelModel", function(newValue, oldValue) {
        if (newValue == undefined) return;
        //$scope.contentSelModel.number = $scope.contentSelModel.number || 1;
        $scope.contentSelModel.text="最大数量为("+newValue.actual_quantity+")";
        $scope.$emit("updateTotalPrice");
      })

      // 监听添加数量的变化
      $scope.$watch("contentSelModel.number", function(newValue, oldValue) {
        if (newValue == undefined) return;
        $scope.$emit("updateTotalPrice");
      })

      // 监听退货数量的变化
      $scope.$watch("returnContents", function(newArr, oldArr) {
        if (newArr == undefined) return;

        newArr.filter(function(item) {
            if (parseFloat(item.number) > parseFloat(item.actual_quantity)) {
              var oldItem = oldArr.filter(function(model) {
                  return item.id == model.id;
              })[0];

              $scope.dialogFun(item.actual_quantity, function() {
                  item.number = oldItem.number;
              });
            } else {
              item.totalPrice = item.number * item.actual_price;
            }
        })

        $scope.validateExitFloat();
        
        $scope.$emit("finalReturnPrice");
      }, true)
    }
  }
  common.init();
}]);