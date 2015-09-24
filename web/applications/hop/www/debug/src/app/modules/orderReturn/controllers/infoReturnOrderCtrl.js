'use strict'
angular.module('hop').controller('infoReturnOrderCtrl', ['$location', 'dialog', 'req', '$scope', '$cookieStore', '$upload', 'appConfigure','$state', function($location, dialog, req, $scope, $cookieStore, $upload, appConfigure,$state) {
	var common = {
		init: function() {
		   // 初始化参数
		   this.param();
		   // 获取退货退款单信息
		   this.request.view();
		   // 获取备注列表
		   this.request.log();
		   // VM对象监听
           this.scopeWatch();
		},
		param: function() {
           $scope.imgUploads=[];
		},
		request: {
			view:function(){ 
				req.getdata('/rejected/view', 'POST', function(json) {
					if (json.status == 0) {
						$scope.info = json.rejected_info;
	                    $scope.reasons=json.reasons;
	                    $scope.deal_methods=json.deal_methods;
                        $scope.roleID = json.user_info.role_id;
						$scope.reasons.filter(function(item){
	                       if(item.id==$scope.info.reason){
	                       	  $scope.info.reasonModel=item;
	                       }
						})

						$scope.deal_methods.filter(function(item){
	                   if(item.id==$scope.info.deal_method){
	                     	  $scope.info.dealMethod=item;
	                        }
					  })
					}
			    }, {"rejected_id": $state.params.id});
			},
            log:function(){
	            req.getdata('/rejected/log_list', 'POST', function(json) {
	                $scope.logs=json.list;
	            },{"rejected_id": $state.params.id});
	        },
	        change:function(status){
	        	//  策略模式(封装业务实现)
	        	var excute={
	        		// 客服处理
	        		customer:function(){
                      this.post('/rejected/operator_change_status');
	        		},
	        		// 物流处理
	        		logistics:function(){
                      this.post('/rejected/logistics_change_status');
	        		},
	        		// 财务处理
	        		finance:function(){
	        			var _this = this;
	        			if($scope.imgUploads.length==0){
		        			dialog.tips({
			 			       bodyText: "退款凭证尚未上传，请问是否确定已处理？",
			 			       ok: function() {
			 			       	  _this.post('/rejected/finance_change_status');
			 			       },
			 			       actionText:'确定',
			 			       closeText:'取消'
			 			    });
		        		}else{
		        			_this.post('/rejected/finance_change_status');
		        		}
	        		},
	        		post:function(url){
                       req.getdata(url, 'POST', function(json) {
				            if(json.status==0){
				               common.request.update();
				               common.request.log();
				            }else if(json.status==-2){
				               dialog.tips({bodyText: json.msg});
				            }
			           },{"rejected_id": $state.params.id},true);
	        		}
	        	}
                excute[status]();
	        },
	        close:function(msg){
              req.getdata('/rejected/close_rejected', 'POST', function(json) {
              	   if(json.status==0){
                      common.request.view();
                      common.request.log();
                   }else if(json.status==-2){
                      dialog.tips({bodyText: json.msg});
                   }
	          },{"rejected_id": $state.params.id,content:msg});
	        },
	        remark:function(msg){
                req.getdata('/rejected/add_remark', 'POST', function(json) {
	                if(json.status == 0){
                    common.request.log();
                  }else if(json.status==-2){
                    dialog.tips({bodyText: json.msg});
                  }
	            }, {rejected_id:$state.params.id, content:msg},true);
	        },
	        update:function(){
	        	var postData = {
	                    reason:$scope.info.reasonModel.id,
	                    deposit_bank:$scope.info.deposit_bank, 
	                    bank_number:$scope.info.bank_number, 
	                    account_holder:$scope.info.account_holder, 
	                    rejected_id: $state.params.id,
                        suggestion :$scope.info.suggestion,
	                    evidence:"" 
	        	}
                
                // 财务处理，上传凭据
	        	if($scope.imgUploads.length>0)
	        		 postData.evidence=$scope.imgUploads[0].dataUrl;

                req.getdata('/rejected/update', 'POST', function(json) {
	                if(json.status == 0) {  
                     common.request.view();
                  }else if(json.status==-2){
                     dialog.tips({bodyText: json.msg}); 
                  }
	            },postData,true);
	        }
		},
		scopeWatch: function() {
			// 监控上传选中的文件
			$scope.$watch('files', function() {
				if ($scope.imgUploads && $scope.imgUploads.length > 1) {
					alert('目前允许上传1张图片！');
					return;
				}

				if ($scope.files != undefined) {
					$scope.upload('imgUploads', 'files');
				}
			});

			// 上传文件
			$scope.upload = function(key, name) {
				angular.forEach($scope[name], function(v) {
					$upload.upload({
						url: 'http://img.dachuwang.com/upload?bucket=misc',
						file: v,
						fileFormDataName: 'files[]'
					}).progress(function(evt) {
						var progressPercentage = parseInt(100.0 * evt.loaded / evt.total);
						v['progressPercentage'] = progressPercentage;
					}).success(function(data, status, headers, config) {
						// 成功后预览
						v['dataUrl'] = data['files'][0]['url'];
						$scope.imgUploads.push(v);
					});
				});
			};
            
            // 删除图片
			$scope.fileCancel=function($index){
                $scope.imgUploads.filter(function(item,index){
                	 if($index==index)
                	 	 $scope.imgUploads.splice($scope.imgUploads.indexOf(item),1);
                })
			}
            
            // 删除上传凭据
			$scope.evidenceCancel=function(){
				$scope.info.refund_evidence="";
				$scope.imgUploads.length=0;
			}
            
            // 添加备注
			$scope.setRemark = function() {
 			    dialog.tips({
 			       bodyText: "",
 			       ok: function(remark_msg,flag) {
 			       	   if(flag==1) 
 			             common.request.remark(remark_msg);
 			       },
 			       actionText:'确定',
 			       closeText:'取消'
 			    }, {templateUrl: 'set_remark.html'});
 			};
            
            // （物流、财务、运营）权限处理流程
 			$scope.excuteChange=function(status){
               common.request.change(status);
 			}
            
            // 更新操作
 			$scope.update=function(){
               common.request.update();
 			}
            
            // 关闭操作
 			$scope.close=function(){
                dialog.tips({
 			       bodyText: "",
 			       ok: function(remark_msg,flag) {
 			       	  if(flag==1) 
 			             common.request.close(remark_msg);
 			       },
 			       actionText:'确定',
 			       closeText:'取消'
 			    }, {templateUrl: 'close_order.html'});
 			}
            
            // 返回操作
			$scope.back=function(){
				window.history.go(-1);
			}
		}
	}
	common.init();
}]);
