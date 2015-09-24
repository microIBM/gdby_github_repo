'use strict';

angular
  .module('hop')
  .controller('ComplaintEditCtrl', ['dialog', '$location', '$upload', 'daChuLocal', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', '$state', function(dialog, $location, $upload, daChuLocal, req, $scope, $modal, $window, $cookieStore, $stateParams, $state) {
  $scope.id = $stateParams.id;
  $scope.deal_price = {};

  var getInfo = function() {
    req.getdata('complaint/edit_input', 'POST', function(data){
      if(data.status == 0) {
        $scope.order = data.order;
        $scope.data = data.info;
        $scope.dealResult = data.info.deal_result;
        $scope.resultParam = data.info.result_param;
        $scope.data.order_id = $scope.data.order_number +"（"+$scope.data.order_id+"）";
        $scope.data.suborder_id= $scope.data.suborder_number +"（"+$scope.data.suborder_id+"）";
        $scope.contents = [];
        $scope.imgUploads = data.info.images;
        $scope.ctypeList = data.ctypes;
        $scope.feedbackList = data.feedbacks;
        $scope.statusList = data.statuses;
        $scope.saleList = data.sales;
        $scope.logisticsList = data.logistics;
        $scope.sourceList = data.sources;
        angular.forEach(data.info.contents, function(value, key) {
          angular.forEach(data.order.detail, function(v, k) {
            if(v.product_id == value.product_id) {
              v.single_price = value.single_price;
              $scope.contents.push({product: v, quantity: value.quantity, sumPrice: value.sum_price, price: value.price});
            }
          });
        });

        $scope.relationContents = data.relation_content;
        $scope.dealResults= data.deal_result;
        $scope.relationContents.filter(function(item){
           if(item.code == $scope.data.relation_content){
              $scope.data.relation_content=item;
           }
        })

        $scope.dealResults.filter(function(item){
           if(item.code == $scope.data.deal_result){
              $scope.data.deal_result=item;
           }
        })

        angular.forEach($scope.feedbackList, function(value, key) {
          if(value.code == $scope.data.feedback){
            $scope.data.feedback = value;
          }
        });
        angular.forEach($scope.ctypeList, function(value, key) {
          if(value.code == $scope.data.ctype){
            $scope.data.ctype = value;
          }
        });
        angular.forEach($scope.saleList, function(value, key) {
          if(value.id == $scope.data.sale_id){
            $scope.data.sale = value;
          }
        });
        angular.forEach($scope.logisticsList, function(value, key) {
          if(value.id == $scope.data.logistics_id){
            $scope.data.logistics = value;
          }
        });
        angular.forEach($scope.statusList, function(value, key) {
          if(value.code == $scope.data.status){
            $scope.data.status = value;
          }
        });
        angular.forEach($scope.sourceList, function(value, key) {
          if(value.code == $scope.data.source){
            $scope.data.source = value;
          }
        });
      }
    },{id: $scope.id});
  };
  getInfo();

  $scope.back = function() {
    history.go(-1);
  };
  
  // 退货商品功能
  (function(){
      $scope.contentSelModel=null;
      $scope.returnContents = [];

     // 更新正在选退货商品的价格
      $scope.$on("updateTotalPrice", function(eve) {
        if($scope.contentSelModel.number>0)
          $scope.contentSelModel.totalPrice = $scope.toFixedNumber($scope.contentSelModel.number * $scope.contentSelModel.single_price,2);
        else 
          $scope.contentSelModel.totalPrice = 0;
      })

      // 计算总退款金额
      $scope.$on("finalReturnPrice", function(eve) {
        $scope.data.total_price = 0;
        $scope.returnContents.filter(function(item) {
          $scope.data.total_price += parseFloat(item.totalPrice);
        })
      })
      
      // 金额的四舍五入计算
      $scope.toFixedNumber = function(input,param){
          var number = new Number(input);
          return number.toFixed(param);
      }

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
        });
      }

      // 增加
      $scope.addContent = function(model) {
        if (model.number==undefined) {
        }else if (parseInt(model.number) > parseInt(model.quantity)) {
          $scope.dialogFun(model.quantity, null);
        } else {
          model = $scope.data.contents.splice($scope.data.contents.indexOf(model), 1)[0];
          $scope.contentSelModel = null;
          $scope.returnContents.push(model);
          $scope.$emit("finalReturnPrice");
        }
      }

      // 减去
      $scope.reduceContent = function(model) {
        model = $scope.returnContents.splice($scope.returnContents.indexOf(model), 1);
        $scope.data.contents.push(model[0]);
        $scope.$emit("finalReturnPrice");
      }

      $scope.$watch("data.deal_result",function(newValue,oldValue){
          if (newValue == undefined) return;
          
          if(newValue.code!=$scope.dealResult){
             $scope.data.result_param=""
          }else {
             $scope.data.result_param=$scope.resultParam
          }
      });

      // 监听筛选退货商品的变化
      $scope.$watch("contentSelModel", function(newValue, oldValue) {
        if (newValue == undefined) return;

        $scope.contentSelModel.text="最大数量为("+newValue.quantity+")";
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
            if (parseInt(item.number) > parseInt(item.quantity)) {
              var oldItem = oldArr.filter(function(model) {
                  return item.id == model.id;
              })[0];

              $scope.dialogFun(item.quantity, function() {
                  item.number = oldItem.number;
              });
            } else {
              item.totalPrice =  $scope.toFixedNumber(item.number * item.single_price,2);
            }
        })
        $scope.$emit("finalReturnPrice");
      }, true)
  })();

  // 修改投诉单单
  $scope.edit = function(order_number) {
    $scope.show_error = true;
    $scope.basic_form.$setDirty();
    if($scope.basic_form.$invalid) {
      return;
    }

    var feedback = $scope.data.feedback || {code:0},
        source = $scope.data.source || {code:0},
        ctype = $scope.data.ctype || {code:0},
        sale = $scope.data.sale || {id: 0},
        logistics = $scope.data.logistics || {id: 0},
        status = $scope.data.status || {code: 0};

    // 判断（整单相关，还是SKU相关）
    var postData = {
      result_param:$scope.data.result_param,
      deal_result:$scope.data.deal_result==null?'':$scope.data.deal_result.code,
      relation_content:$scope.data.relation_content.code,
      id: $scope.data.id,
      orderNumber: $scope.data.suborder_number,
      source: source.code,
      feedback: feedback.code,
      ctype: ctype.code,
      contents: $scope.contents,
      description: $scope.data.description,
      imgUploads: $scope.imgUploads,
      suggest: $scope.data.suggest,
      progress1: $scope.data.progress1,
      saleId: sale.id,
      progress2: $scope.data.progress2,
      logisticsId: logistics.id,
      progress3: $scope.data.progress3,
      solution : $scope.data.solution,
      status: status.code,
    };

    req.getdata('/complaint/edit', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'修改投诉单成功！'});
        req.redirect('/complaint/list');
      } else {
        dialog.tips({bodyText:'修改投诉单失败。'});
      }
    }, postData, true);
  };

  $scope.dialog = dialog.tips;

  // 上传图片
  $scope.$watch('files', function () {
    console.log($scope.imgUploads);
    if($scope.imgUploads && $scope.imgUploads.length >= 4) {
      alert('最多允许上传5张图片！');
      return;
    }
    if($scope.files != undefined) {
      $scope.upload('imgUploads', 'files');
    }
  });

  // 上传文件
  $scope.upload = function (key, name) {
    angular.forEach($scope[name], function(v) {
      $upload.upload({
        url: 'http://img.dachuwang.com/upload?bucket=misc',
        file: v,
        fileFormDataName: 'files[]'
      }).progress(function (evt) {
        var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
        v['progressPercentage'] = progressPercentage;
      }).success(function (data, status, headers, config) {
        // 成功后预览
        v['dataUrl'] = data['files'][0]['url'];
        
        $scope.imgUploads.push({
          size: v['size']+'bytes',
          dataUrl: data['files'][0]['url'],
          progressPercentage: v['progressPercentage'],
          type: v["type"]
        });

      });
    });
  };

  // 取消上传文件
  $scope.picCancel = function(index) {
    $scope.imgUploads.splice(index, 1);
    console.log($scope.imgUploads);
  }

}]);
