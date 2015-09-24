'use strict'
angular.module('hop').controller('CustomerEditCtrl',['$location', 'dialog',  'req', '$scope', '$stateParams','$upload', function($location, dialog, req, $scope, $stateParams, $upload){
  // 下拉列表框相关数据
  $scope.addr = {
    province: '',
  };

  // 初始化，获取客户编辑相关数据
  var common = {
        initEditData:function(list,id){
             return list.filter(function(item){
                     return item.value==id;
             })[0];
        },
        init :function() {
          var postData = {id : $stateParams.id};
          
          // 对账-半月对象
          $scope.kaCheckDateHalfMonth = [{
              last:"",
              next:"",
              list:[]
          }];
          
          // 开票-半月对象
          $scope.kaInvoiceDateHalfMonth=[{
              last:"",
              next:"",
              list:[]
          }];
          
          // 付款-半月对象
          $scope.kaPayDateHalfMonth=[{
              last:"",
              next:"",
              list:[]
          }];

          // 获取参数
          req.getdata('customer/edit_input', 'POST', function(data) {
            if(data.status == 0) {
              // 客户编辑详情
              $scope.info = data.info;
              // 子账号列表
              $scope.children=data.children;
              $scope.provinces = data.provinces;
              $scope.allLines = data.lines;
              $scope.sites = data.sites;
              $scope.directionList = data.directions;
              $scope.dimensionList = data.dimensions;
              $scope.customerLists = data.types;
              $scope.estimated = data.estimated;
            
               // 银行列表
              $scope.banks=data.banks; 
              $scope.kaSeedCustomer=data.info.ka_seed==1?true:false;
              //  KA客户类型列表
              $scope.account_types=data.account_types;
               // 结账日期列表
              $scope.billing_cycles=data.billing_cycles;

              $scope.checkDateArr = data.check_dates;
              $scope.check_dates=[]; //对账日期

              // 母账号手机号
              $scope.kaParentMobile=data.info.parent_mobile;

              // KA客户账号类型
              $scope.kaCustomerType=common.initEditData(data.account_types,data.info.account_type);   
              // 开户银行
              $scope.kaBank=common.initEditData(data.banks,data.info.bank);

              $scope.kaBillCycle=data.info.billing_cycle==""?$scope.billing_cycles[0]:data.info.billing_cycle;

              if(data.info.billing_cycle!="")
                 // KA结账日期
                 $scope.kaBillCycle=common.initEditData($scope.billing_cycles,data.info.billing_cycle);    
             
              // KA 开户银行支行     
              $scope.kaUserBankBranch=data.info.sub_bank; 
              // KA 银行账号
              $scope.kaUserBandCard=data.info.bank_account;  

              $scope.greens_meat_estimated = common.initEditData($scope.estimated,data.info.greens_meat_estimated);
              $scope.rice_grain_estimated = common.initEditData($scope.estimated,data.info.rice_grain_estimated); 
              // 关联母账号(KA子账号)
              $scope.kaUserCount=""; 
              
              
              // 结账周期联通
              $scope.$watch("kaBillCycle",function(_new,_old){
                  if(_new ==undefined) return;

                  angular.forEach( $scope.checkDateArr, function(model,k) {
                    
                     if(k==_new.value){
                          $scope.check_dates=model;

                          $scope.kaCheckDateHalfMonth[0].list=model;

                          // 处理付款日期的了逻辑
                          $scope.pay_check_new=[];
                          if(_new.value!="none" && _new.value!="offline_billing" ){
                               $scope.pay_check_new = data.pay_dates;
                          }else if(_new.value=="offline_billing"){
                               $scope.pay_check_new = $scope.check_dates;
                          } else if(_new.value=="none"){
                              $scope.pay_check_new = $scope.check_dates;
                          }
                           
                          // 半月处理
                          if(_new.value == "half_month"){
                              $scope.kaPayDateHalfMonth[0].list=$scope.pay_check_new;
                              var splitCheckDate= $scope.info.check_date.split(",");
                              // 对账
                              $scope.kaCheckDateHalfMonth[0].last=common.initEditData(model[0],splitCheckDate[0]);
                              $scope.kaCheckDateHalfMonth[0].next=common.initEditData(model[1],splitCheckDate[1]);
                              
                              // 付款
                              $scope.kaPayDate=$scope.info.pay_date; 
                          }else{ 
                              if(_new.value=="none" || data.info.billing_cycle=="") {
                                  $scope.kaCheckDate="none";
                                  $scope.kaPayDate="none";
                              }else{
                                  $scope.kaCheckDate=data.info.check_date;
                                  $scope.kaPayDate=data.info.pay_date;
                              }
                           
                              // KA对账日期
                              $scope.kaCheckDate=common.initEditData($scope.check_dates,$scope.kaCheckDate);
                          } 
                          
                          // KA付款日期 
                          if ($scope.info.billing_cycle==_new.value){  
                              console.log($scope.pay_check_new);
                              console.log($scope.kaPayDate);
                              $scope.kaPayDate=common.initEditData($scope.pay_check_new, $scope.kaPayDate); 
                          
                          }else if (_new.value=="none"){
                               $scope.kaPayDate=$scope.pay_check_new[0];

                          }else if (_new.value=="offline_billing"){
                              $scope.kaCheckDate = $scope.check_dates[0];
                              $scope.kaPayDate = $scope.check_dates[0];

                          } else {
                              $scope.kaPayDate=null;
                          }
                     }
                  });
              })

              // 母账号参数
              $scope.$on("setKaMotherParam",function(data,param){
                   param.account_type= $scope.kaCustomerType==undefined?"":$scope.kaCustomerType.value;
                   param.billing_cycle= $scope.kaBillCycle==undefined?"":$scope.kaBillCycle.value;
                   param.invoice_title=$scope.info.invoice_title;
                   if(param.billing_cycle=="half_month"){
                      param.check_date=$scope.kaCheckDateHalfMonth[0].last.value+","+$scope.kaCheckDateHalfMonth[0].next.value;
                      //param.invoice_date=$scope.kaInvoiceDateHalfMonth[0].last.value+","+$scope.kaInvoiceDateHalfMonth[0].next.value;
                      //param.pay_date=$scope.kaPayDateHalfMonth[0].last.value+","+$scope.kaPayDateHalfMonth[0].next.value;
                   }else{
                     param.check_date=$scope.kaCheckDate==undefined?"":$scope.kaCheckDate.value;
                     //param.invoice_date=$scope.kaInvoiceDate==undefined?"":$scope.kaInvoiceDate.value;;
                     //param.pay_date=$scope.kaPayDate==undefined?"":$scope.kaPayDate.value;
                   }

                   param.pay_date=$scope.kaPayDate==undefined?"":$scope.kaPayDate.value;
              });

              //  子账号参数
              $scope.$on("setKaChildParam",function(data,param){
                   param.account_type=$scope.kaCustomerType.value;
                   param.parent_mobile=$scope.kaParentMobile;
              });

              // 初始化客户类型
              if($scope.info.customer_type) {
                angular.forEach( $scope.customerLists, function(v) {
                  if(v.value == $scope.info.customer_type){
                    $scope.customerModel = v;
                  }
                });
              }

              $scope.shopTypes = data.shop_type;
              //展示已选的餐饮类别
              $scope.shop = (function(t){
                var type = parseInt(t);
                var i,len = $scope.shopTypes.length;
                for(i=0; i<len; i++) {
                  if($scope.shopTypes[i].id === type) {
                    return $scope.shopTypes[i];
                  }
                }
                return null;
              })(data.info.shop_type);
              // 设置地理位置默认选中状态
              if($scope.info.province_id){
                angular.forEach($scope.provinces, function(v){
                  if(v.id == data.info.province_id) {
                    $scope.info.province = v;
                  }
                });
                var lines = [];
                angular.forEach($scope.allLines, function(line) {
                  if($scope.info.province_id == line.location_id){
                    lines.push(line);
                  }
                });
                $scope.lines = lines;
              }
              if($scope.info.line_id) {
                angular.forEach($scope.lines, function(line) {
                  if(line.id == $scope.info.line_id){
                    $scope.info.line = line;
                  }
                });
              }
              if($scope.info.direction) {
                var direction =  $scope.info.direction;
                angular.forEach($scope.directionList, function(v) {
                  if(v.value == direction){
                    $scope.info.direction = v;
                  }
                });
              }
              if($scope.info.dimensions) {
                var dimensions =  $scope.info.dimensions;
                angular.forEach($scope.dimensionList, function(v) {
                  if(v.value == dimensions){
                    $scope.info.dimensions = v;
                  }
                });
              }
            }

            if($scope.info.parent_mobile=="")
              $scope.isShowIcon1=-1;
            else
              $scope.isShowIcon1=0;
            }, postData);

          // 客户类型 和 子母账号 
          $scope.$watch("customerModel",function(_new,_old){
              if(_new==undefined) return;

              if(_new.value!=$scope.info.customer_type){
                 $scope.isAccountType=true;
              }else{
                 $scope.isAccountType=false;
              }
          });

          $scope.$watch("kaCustomerType",function(_new,_old){
              if(_new==undefined) return;
    
              if(_new.value!=$scope.info.account_type){
                 $scope.isCustomerType=true;
              }else{
                 $scope.isCustomerType=false;
              }
          });
          
          
          $scope.inputBlurFun=function(value){
            $scope.kaParentMobile=value;

            // 母账号转成子账号的时候，当前的关联母账号和之前的母账号不能为同一个
            if($scope.info.mobile == value){
              dialog.tips({
                  actionText: '确定' , 
                  headerText: '关联母账号提示操作',
                  bodyText:'不能将自己转成子账号时,母账号为自己!',
                  ok: function() {
                     $scope.kaParentMobile="";
                      $scope.isShowIcon1=2;
                  }
              });
              return;
            }
            if(value==undefined || value.length!=11){
              $scope.isShowIcon1=-1;
              return;
            }else{
               common.validateTelephone(value,null);
            }
          }
        },
        validateTelephone:function(telephone,func){
             if(typeof func != "function")
                 $scope.isShowIcon1=1;
             req.getdata('/customer/check_parent_mobile', 'POST', function(data) {
                   if(typeof func == "function"){
                       func.call(data);
                   }else{
                      if(data.status==0){
                          $scope.isShowIcon1=0;
                       }else {
                          $scope.isShowIcon1=2;
                           dialog.tips({
                                actionText: '确定' , 
                                headerText: '关联母账号提示操作',
                                bodyText:'关联母账号验证不存在!',
                                ok: function() {
                                   $scope.kaParentMobile="";
                                }
                            });
                       }
                   }
             },{parent_mobile:telephone});
        },
        uploadInit:function(){
            $scope.imgUploads = [];
            // 上传图片
            $scope.$watch('files', function () {
                  alert($scope.imgUploads);
                  if($scope.imgUploads && $scope.imgUploads.length >= 5) {
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
                        alert("已经上传了:"+data['files'][0]['url']);
                        console.log(data['files'][0]['url']);
                        // 成功后预览
                        v['dataUrl'] = data['files'][0]['url'];
                        v['size'] += 'bytes';
                        $scope.imgUploads.push(v);
                      });
                  });
            };
            // 取消上传文件
            $scope.picCancel = function(index) {
               $scope.imgUploads.splice(index, 1);
            }
        },
        edit:function(postData){
           // 提交编辑
          req.getdata('customer/edit', 'POST', function(data) {
            if(data.status == 0) {
                dialog.tips({bodyText:'更新客户资料成功！'});
                req.redirect('/customer/list');
            }else  if(data.status == -1) {
                   dialog.tips({bodyText:data.msg, 
                      actionText: '确定' , 
                      ok: function() {
                           $scope.kaParentMobile="";
                      }
                    });
            }else {
                dialog.tips({bodyText:'更新客户资料失败。'});
            }
          }, postData, true);
        }
  }

  // 获取路线
  $scope.getLines = function() {
    if(!$scope.info.province) {
      $scope.lines = $scopes.allLines;
      return;
    }
    $scope.lines = [];
    angular.forEach($scope.allLines, function(value) {
      if($scope.info.province && value.location_id == $scope.info.province.id){
        $scope.lines.push(value);
      }
    });
  };
  
  // 查看子账号
  $scope.searchChildren=function(){
      dialog.tips({
            closeText: '取消',
            actionText: '确定',
            bodyText: '',
            children:$scope.children,
            editChildren:function(id,modal){
                modal.cancel();
                // 跳转到子账号编辑页面
                $location.path("/customer/edit/"+id);
            },
            ok:function(modal){
               modal.cancel();
            }
      },{templateUrl:'childrenAccountList.html'})
  }

  // 编辑客户信息
  $scope.edit = function() {
    // 是否母账号验证通过
    if($scope.isShowIcon1!=0 && $scope.kaCustomerType.value==2){
        $scope.kaParentMobile="";
        dialog.tips({
            actionText: '确定' , 
            headerText: '关联母账号提示操作',
            bodyText:'关联母账号验证不存在!',
            ok: function() {
               $scope.kaParentMobile="";
            }
        });
        return;
    }

    $scope.show_error = true;
    $scope.basic_form.$setDirty();
    if($scope.basic_form.$invalid) {
      return;
    }
    var shopType =0 , isLink = 0;
    shopType = $scope.shop.id || 0;

    var dimensions = $scope.info.dimensions || {value: ''},
        direction = $scope.info.direction || {value: ''};

    // 传参对象
    var postData = {
      id: $stateParams.id,
      name: $scope.info.name,
      shopType: shopType,
      customerType:$scope.customerModel.value,
      isLink: isLink,
      greens_meat_estimated:$scope.greens_meat_estimated.value,
      rice_grain_estimated:$scope.rice_grain_estimated.value,
      dimensions : dimensions.value,
      mobile: $scope.info.mobile,
      provinceId : $scope.info.province.id,
      lineId : $scope.info.line.id,
      address : $scope.info.address,
      direction : direction.value,
      shopName : $scope.info.shop_name,
      remark : $scope.info.remark,
      is_active:$scope.info.is_active,
      recieve_mobile:$scope.info.recieve_mobile,
      recieve_name:$scope.info.recieve_name,
      invoice_title:"",
    };

    // 母账号参数
    if($scope.kaCustomerType.value==1){
       $scope.$emit("setKaMotherParam",postData);
    }
    // 子账号参数
    else if($scope.kaCustomerType.value==2){
       $scope.$emit("setKaChildParam",postData);
    }
    
    // 母账号,种子账户的设置
    if($scope.customerModel.value==2){
       postData.ka_seed=$scope.kaSeedCustomer?1:0;
    }
    
    // 子账号
    if($scope.kaCustomerType.value==2) {
          postData.new_parent_mobile=$scope.kaParentMobile;
      }
     
    // 是否审核通过
    if($scope.isCheckSuccess!=undefined){
       postData.is_active=$scope.isCheckSuccess?1:0;
    }

   
    
    $scope.tips="";
    if($scope.isCustomerType &&  $scope.kaCustomerType.value==2  && $scope.info.child_count>0){
         dialog.tips({
            actionText: '提交修改' ,
            headerText: '更改子账号前操作',
            closeText: '取消',
            bodyText: '',
            tips:$scope.tips,
            status:0,
            kaNewParentMobile:$scope.kaNewParentMobile,
            super:function(value,scope){
                if($scope.info.mobile==value){
                    scope.status=-2;
                    scope.tips="不能指定自己为新的母账号";
                    return;
                };

                if(value.length==11){
                  // 开始提交验证
                  common.validateTelephone(value,function(){
                      if(this.status==0){
                        scope.tips="验证通过";
                      }else{
                        scope.tips="验证不通过";
                      }

                      scope.status=this.status;
                  });
                }
            },
            ok:function(scope,modal){
                 if(scope.kaNewParentMobile!=undefined && scope.status==0){
                    modal.cancel();
                    postData.parent_mobile=scope.kaNewParentMobile;
                    common.edit(postData);
                 }
            }
          },{templateUrl:'edit.html'})
         return;
    }
    
    common.edit(postData);
  };

  // 加载客户信息
  common.init();
  //common.uploadInit();
}]);


