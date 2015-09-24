'use strict';

angular
  .module('hop')
  .controller('ComplaintCreateCtrl', ['dialog', '$location', '$upload', 'daChuLocal', 'req', '$scope', '$modal', '$window','$cookieStore', '$stateParams', '$state', function(dialog, $location, $upload, daChuLocal, req, $scope, $modal, $window, $cookieStore, $stateParams, $state) {
  $scope.data = {};
  $scope.deal_price = {};
  $scope.order_number = $stateParams.order_number;
  var getInfo = function() {
    req.getdata('complaint/create_input', 'POST', function(data){
      if(data.status == 0) {
        $scope.data = data.info;
        $scope.data.order_number = data.order_number ;
        $scope.data.suborder_number= data.suborder_number ;
        $scope.data.order_id = data.order_number+"（"+data.order_id+"）";
        $scope.data.suborder_id= data.suborder_number+"（"+data.suborder_id+"）";
        $scope.ctypeList = data.ctypes;
        $scope.feedbackList = data.feedbacks;
        $scope.statusList = data.statuses;
        $scope.saleList = data.sales;
        $scope.logisticsList = data.logistics;
        $scope.sourceList = data.sources;
        $scope.data.status = $scope.statusList[0];
        
        $scope.data.totalPrice = 0;
        $scope.relationContents= data.relation_content;
        $scope.dealResults= data.deal_result;
      }
    },{order_number: $scope.order_number});

    $scope.contents = [];//[{product:{}, quantity:1, sumPrice:0}];
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
        $scope.data.totalPrice=0;
        $scope.returnContents.filter(function(item) {
          $scope.data.totalPrice += parseFloat(item.totalPrice);
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
        console.log(model);
        if (model.number==undefined) {

        }else if (parseInt(model.number) > parseInt(model.quantity)) {
          $scope.dialogFun(model.quantity, null);
        } else {
          model = $scope.data.detail.splice($scope.data.detail.indexOf(model), 1)[0];
          $scope.contentSelModel = null;
          $scope.returnContents.push(model);
          $scope.$emit("finalReturnPrice");
        }
      }

      // 减去
      $scope.reduceContent = function(model) {
        model = $scope.returnContents.splice($scope.returnContents.indexOf(model), 1);
        $scope.data.detail.push(model[0]);
        $scope.$emit("finalReturnPrice");
      }

      $scope.$watch("data.deal_result",function(newValue,oldValue){
          if (newValue == undefined) return;
          $scope.data.result_param=""
      });

      // 监听筛选退货商品的变化
      $scope.$watch("contentSelModel", function(newValue, oldValue) {
        if (newValue == undefined) return;
        //$scope.contentSelModel.number = $scope.contentSelModel.number || 1;
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
              item.totalPrice = $scope.toFixedNumber(item.number * item.single_price,2);
            }
        })
        $scope.$emit("finalReturnPrice");
      }, true)
  })();

  // 创建投诉单
  $scope.create = function() {
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
        status = $scope.data.status || {code: 0},
        imgUploads = $scope.imgUploads || [];

    // 判断（整单相关，还是SKU相关）
    if($scope.data.relation_content.code==2){
      if($scope.returnContents.length>0){
          $scope.returnContents.filter(function(item){
            var sumPriceFloat= item.single_price * item.number; 
            $scope.contents.push({
               product: item,
               quantity: item.number,
               sumPrice: sumPriceFloat
            })
          });
        }else{
            dialog.tips({
              actionText: '确定',
              bodyText: '请先选择要退货的商品！'
            });
            return;
        }
    } else if ($scope.data.relation_content.code==1){
       $scope.contents=[];
    } 
    

    var postData = {
      result_param:$scope.data.result_param,
      deal_result:$scope.data.deal_result==null?'':$scope.data.deal_result.code,
      relation_content:$scope.data.relation_content.code,
      orderNumber: $scope.data.suborder_number,
      source: source.code,
      feedback: feedback.code,
      ctype: ctype.code,
      contents: $scope.contents,
      totalPrice: $scope.data.totalPrice,
      description: $scope.data.description,
      imgUploads: imgUploads,
      suggest: $scope.data.suggest,
      progress1: $scope.data.progress1,
      saleId: sale.id,
      progress2: $scope.data.progress2,
      logisticsId: logistics.id,
      progress3: $scope.data.progress3,
      solution : $scope.data.solution,
      status: status.code,
    };

    req.getdata('/complaint/create', 'POST', function(data) {
      if(data.status == 0) {
        dialog.tips({bodyText:'添加投诉单成功！'});
        req.redirect('/complaint/list');
      } else {
        dialog.tips({bodyText:'添加投诉单失败。'});
      }
    }, postData, true);
  };

  $scope.dialog = dialog.tips;

  // 上传图片
  $scope.$watch('files', function () {
    if($scope.imgUploads && $scope.imgUploads.length >= 4) {
      alert('最多允许上传5张图片！');
      return;
    }
    if($scope.files != undefined) {
      $scope.upload('imgUploads', 'files');
    }
  });

  var imgUpload = [];

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
        imgUpload.push({
          size: v['size']+'bytes',
          dataUrl: data['files'][0]['url'],
          progressPercentage: v['progressPercentage'],
          type: v["type"]
        });
        console.log(imgUpload);

        $scope[key] = imgUpload;
        daChuLocal.set(key, $scope[key]);
      });
    });
  };

  // 取消上传文件
  $scope.picCancel = function(index) {
    $scope.imgUploads.splice(index, 1);
  }
}]);
