'use strict';

angular
  .module('hop')
  .controller('CustomerNewTransferCtrl',['$location', 'dialog',  'req', '$scope','$filter','$cookieStore','daChuLocal', function($location, dialog, req, $scope, $filter, $cookieStore,localStorage){
  // 公海、私海切换标识
  $scope.show_status = "1";

  // 是否显示移交模块
  $scope.show_list = false;
  
  // (私海、公海)切换操作
  $scope.filterByStatus = function($status) {
      if($status==$scope.show_status){
    	   	return;
      } else {
    	  	 if($scope.show_list){
    	        dialog.tips({bodyText:'你确定要切换吗?',actionText:"确定",ok:function(){
    	            $scope.show_list = false;
    	            $scope.show_status = $status;
                  $scope.modelS.inputId=-1;
                  commonL.initLoadRequest();
                  commonL.initParam(); 
    	        }});
    	  	 }else{
    	  	   $scope.show_status = $status;
             $scope.disabledS=false;
             $scope.disabledG=false;
             $scope.modelS.inputModelS="";
             $scope.modelL.inputModelL="";
             $scope.modelS.inputId=-1;
             $scope.modelL.inputId=-1;
             commonGongHai.initParam();
             commonSiHai.initParam();
    	  	 }
  	  }  
  };

  $scope.$watch("show_status",function(){
       commonL.removeCheckAll();
  })
  
  // 移交模块：公海操作
  var commonGongHai={
  	  search:function(){
          $scope.basic_form_g.$setDirty();
          
          //   验证操作
          if($scope.basic_form_g.$invalid){ 
               //
               return;
          }else{
             $scope.disabledG=true;
             $scope.show_list=true;
             commonL.setSystemAndCity($scope.modelParamG.system,$scope.modelParamG.city,$scope.modelParamG.type,"g");
          }
  	  },
  	  reset:function(){
           $scope.show_list = false;
           $scope.disabledG = false;
           this.initParam();
           commonL.reset();
  	  },
  	  initParam:function(){
          $scope.modelParamG={
             system:"",
             city:"",
             type:""
          }
  	  },
  	  init:function(){
           this.initParam();
           $scope.systemModelG=[];
           $scope.cityModelG=[];
           $scope.typeModelG=[];
  	  }
  }
  
  // 移交模块：私海操作
  var commonSiHai={
  	  search:function() {
            if(!$scope.modelS.selClickItem) {
                 $scope.modelS.inputModelS="";
                 return;
            }

            $scope.basic_form_s.$setDirty();
       
            //   验证操作
            if($scope.basic_form_s.$invalid) { 
                return;
            } else {
               $scope.disabledS=true;
               $scope.show_list=true;
               commonL.setSystemAndCity($scope.modelParamS.system,$scope.modelParamS.city,$scope.modelParamS.type,"s");
            }
  	  },
  	  reset:function(){
             $scope.show_list = false;
             $scope.disabledS = false;
             $scope.modelS.inputModelS = "";
             this.initParam();
             commonL.reset();
  	  },
    	initParam:function(){
            $scope.modelParamS={
               system:"",
               city:"",
               sale_role:"",
               sale_name:"",
               type:""
            }
  	  },
      selectDropSaleName:function(){
            $scope.modelS={statusModel:false,inputModelS:"",inputId:-1,dropList:[],selClickItem:false,selClickModel:{}};

            $scope.keyFunS=function($event) {
               $event.preventDefault();
               $event.stopPropagation();

               $scope.modelS.statusModel=true;
               $scope.modelS.changeOther=false;
            }

            $scope.clickItemS=function(_value,_id,_model) {
               if(_value=="姓名没有找到"){
                   return;
               }
               $scope.modelS.selClickItem=true;
               $scope.modelS.statusModel=false;
               $scope.modelS.inputModelS=_value;
               $scope.modelS.inputId=_id;
               $scope.modelS.selClickModel=_model;
               $scope.$emit("updateModule",_model.role_id,_model.province_id,_model.site_id);
            }
           
            $scope.$watch("modelS.inputModelS",function(_new,_old){
                if($scope.modelS.changeOther) {return;}

                var _city=$scope.modelParamS.city,
                      _role=$scope.modelParamS.sale_role;
                
                var dropList=$scope.salesList||[];
                if((_city!="" && _city!=null) 
                        || (_role!=null && _role!="" )) {
                        $scope.$emit("updateSaleName",$scope.modelParamS.city,$scope.modelParamS.sale_role);
                        dropList=$scope.saleNameModelS;
                }

                $scope.modelS.statusModel=true;

                
                // 过滤
                var model=  $filter('filter')(dropList,{name:_new});
                if(model.length>0 && $scope.modelS.selClickItem==true && _new!=""){
                }else{
                   $scope.modelS.selClickItem=false;
                }
              
                if(model.length==0){
                   $scope.saleNameModelS=[{name:"姓名没有找到"}];
                }else {
                   $scope.saleNameModelS=model;
                }
            })

            $scope.$on("updateModule",function(event,_role_id,_province_id,_site_id){
                // /$scope.modelParamS.system=$filter("filter")($scope.systemModelS,{id:_site_id})[0];
                $scope.modelParamS.city=$filter("filter")($scope.cityModelS,{id:_province_id})[0];
                //$scope.modelParamS.sale_role=$filter("filter")($scope.saleRoleModelS,{code:_role_id})[0];
            });
      },
  	  init:function(){
      	  	this.initParam();

            $scope.systemModelS=[];
            $scope.cityModelS=[];
            $scope.saleRoleModelS=[];
            $scope.typeModelS=[];

            this.selectDropSaleName();
            $scope.$on("updateSaleName",function(event,_city,_role){
                  //alert(_system.id+","+_city.id+","+_role.code);
                  var _filterModel=$scope.salesList;

                  if(_city!="" && _city!=null) {
                     _filterModel = $filter("filter")(_filterModel,{ province_id:_city.id});
                  }
                      
                  if(_role!=null && _role!="" ){
                      _filterModel = $filter("filter")(_filterModel,{ role_id:_role.code});
                  }


                  $scope.saleNameModelS=_filterModel;
               
                  console.log($filter("filter")(_filterModel,{id:$scope.modelS.inputId}).length);

                  if($filter("filter")(_filterModel,{id:$scope.modelS.inputId}).length===0){
                      $scope.modelS.changeOther=true;
                      $scope.modelS.inputModelS="";
                      $scope.modelS.statusModel=false;
                      $scope.modelS.selClickItem=false;
                  }
            })

            $scope.$watch('modelParamS.city',function(param1,param2){
                 if(param1=="") return; 
                 $scope.$emit("updateSaleName",param1,$scope.modelParamS.sale_role);
            })

            $scope.$watch('modelParamS.sale_role',function(param1,param2){
                  if(param1=="") return; 
                  $scope.$emit("updateSaleName",$scope.modelParamS.city,param1);
                  //$scope.typeModelS = $filter("filter")($scope.typeModelSC,{type:$scope.modelParamS.sale_role.msg});
            })
    	}
  }

  // 接收模块：移交客户操作
  var commonL={
      //  查询操作
      search:function(){
            $scope.isClickShowCheck=false;

            $scope.basic_form_l.$setDirty();
           
            //   验证操作
            if($scope.basic_form_l.$invalid){ 
                return;
            }else{
               $scope.show_list=true;
            }    
            //$scope.isAjaxLoading=true;
            this.getList();
      },
      //  获取列表操作
      getList:function(){
            if(!$scope.show_list)  
               return;

            if(!$scope.modelL.selClickItem && $scope.modelL.inputModelL!=""){
                dialog.tips({bodyText:'未查找到销售姓名,请筛选点击销售姓名!',actionText:"确定",ok:function(){
                      $scope.modelL.inputModelL="";
                }});
                return;
            }

            var dataParam={
                  "provinceId": $scope.modelParamL.city.id,  // 所属城市
                  "lineId": $scope.modelParamL.line==null?"":$scope.modelParamL.line.id,        // 所属线路
                  "orderRecord":$scope.modelParamL.orderRecord==null?0:$scope.modelParamL.orderRecord.code,
                  "customerType": $scope.modelParamL.customerType.code, // 客户类型
                  "saleId": $scope.modelL.inputId,        // 销售ID
                  "key": $scope.modelParamL.searchValue,        // 搜索关键词
                  "currentPage": $scope.paginationConf.currentPage||1,      // 第几页
                  "itemsPerPage": $scope.paginationConf.itemsPerPage     // 每页显示几条
            }
            console.log(dataParam);

            req.getdata('/customer/lists_transfer', 'POST', function(data) {
                if(data.status == 0) {
                  $scope.check_all=false;
                  $scope.listSearch=data.list;                  
                  $scope.paginationConf.totalItems = data.total;
                  commonL.pageShowCheck($scope.listSearch);
                }
            },dataParam,true);        
      },
      //  分页参数初始化
      pagerInitSetting:function(){
            // 分页cookie纪录
            var startPage = $cookieStore.get('paginationCookie');
            $scope.getpage=function(){
                $cookieStore.put('paginationCookie',$scope.paginationConf.currentPage);
            }
            // 分页参数初始化
            $scope.paginationConf = {currentPage: startPage, itemsPerPage: 15, totalItems: 0 };

            // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
            $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', this.getList); 
      },
      //  重置操作
      reset:function(){
            $scope.modelParamL.line="";
            $scope.modelParamL.selTotalCount=0;
            $scope.modelParamL.sale_role="";
            $scope.modelParamL.status="";
            $scope.modelL.inputModelL="";
            $scope.modelParamL.searchValue=""; 
            $scope.modelL.disabledL=true;    
            $scope.modelParamL.first_order="";
            $scope.modelParamL.selTotalCount_regist=0;
            $scope.modelParamL.selTotalCount_potient=0;
            $scope.modelS.inputId=-1;
            $scope.modelL.inputId=-1;
            $scope.listSearch=[];
            $scope.statusModelL=[];
      }, 
      //  点选移交客户
      checkOne:function(_checkBool,_model){
            var _cacheList=localStorage.get("$listSelectSearch");
            
            if(!_cacheList)
                 localStorage.get("$listSelectSearch",[]);
           
            // 设置缓存
            this.pushCacheCheck(_checkBool,_model);
      },
      //  点击全选
      checkAll:function(_bool){
             var _cacheList=localStorage.get("$listSelectSearch");
            
             if(!_cacheList)
                 localStorage.get("$listSelectSearch",[]);

             if(_bool){
                  $scope.check_all=true;
              } else {
                  $scope.check_all=false;
              } 

              angular.forEach($scope.listSearch, function(value, key) {
                   value.checked = $scope.check_all;
                   commonL.pushCacheCheck($scope.check_all,value)
              });
              

              if(!_bool){
              var  _cacheListModel = localStorage.get("$listSelectSearch");
              var   _potient=$filter("filter")(_cacheListModel,{customerType:1}).length;
              var   _regist=$filter("filter")(_cacheListModel,{customerType:2}).length;
                if(_regist==0){
                     $scope.modelParamL.selTotalCount_regist=0;
                }

                if(_potient==0){
                     $scope.modelParamL.selTotalCount_potient=0;
                }
             }
      },
      pageShowCheck:function(_list){
             var _cacheModelList = localStorage.get("$listSelectSearch");
             if(_cacheModelList.length==0 && (_cacheModelList instanceof Array))
                 return;

             _cacheModelList.filter(function(_cacheModel){
                  _list.filter(function(_model){
                       if(_cacheModel.id == _model.id){
                            _model.checked = true;
                        }
                  })
             })
      },
      //  设置移交客户到缓存
      pushCacheCheck:function(isSet,_model){
              var _cacheModelList = localStorage.get("$listSelectSearch");

              if(_cacheModelList!=null && _cacheModelList.length>0 ) {
                // 根据id找到一个移交客户
                var _findOneModel=_cacheModelList.filter(function(item){
                     return item.id==_model.id;
                })[0];
                
                // 判断移交对象是否缓存, （isSet=false）执行删除操作
                if(_findOneModel && !isSet){
                    var _removeOneList=_cacheModelList.filter(function(item){
                       return item.id!=_model.id;
                    });
                    localStorage.set("$listSelectSearch",[]);
                    
                    
                    // 移除后是否存在数据
                    if(_removeOneList.length>0){
                        console.log(_removeOneList);
                        this.setCheckModel(_removeOneList);
                    }else{
                        // 潜在客户
                        if(_findOneModel.customerType==1){
                             $scope.modelParamL.selTotalCount_potient=0;
                        }
                        // 注册客户
                        else if(_findOneModel.customerType==2){
                             $scope.modelParamL.selTotalCount_regist=0;
                        }
                    } 

                }else if(!_findOneModel && isSet) {
                        console.log("存在啊");
                        if(_cacheModelList.length==0)
                             localStorage.set("$listSelectSearch",[]);

                        this.setCheckModel(_model);
                }else{
                    //alert("没有这个判断")
                }
              }else if(isSet){
                  localStorage.set("$listSelectSearch",[]);
                  this.setCheckModel(_model);
              }
      },
      //  处理移交客户实体
      setCheckModel:function(_model){
            var _cacheList = localStorage.get("$listSelectSearch");

            if( (_cacheList.length == 0) && (_model instanceof Array)){
                localStorage.set("$listSelectSearch",_model)
            }else {
                  var model = {
                       customerType:$scope.modelParamL.customerType.code,
                       id:_model.id,
                       shop_name:_model.shop_name,
                       mobile:_model.mobile,
                       line_name:_model.line_name,
                       sale:_model.sale,
                       shop_type_name:_model.shop_type_name,
                       address:_model.address,
                       bd_name:_model.bd_name,
                       am_name:_model.am_name,
                       order_record:_model.order_record
                  }
                  _cacheList.push(model);
                  localStorage.set("$listSelectSearch",_cacheList);
            }
              
            var cacheList=localStorage.get("$listSelectSearch");
            var   _potient=$filter("filter")(cacheList,{customerType:1}).length;
            var   _regist=$filter("filter")(cacheList,{customerType:2}).length;
            if(cacheList.length>0){
                $scope.modelParamL.selTotalCount=cacheList.length;
                $scope.modelParamL.selTotalCount_potient=_potient;
                $scope.modelParamL.selTotalCount_regist=_regist;
            }
      },
      //  清除移交客户操作
      removeCheckAll:function(){
           localStorage.set("$listSelectSearch",[]);
           $scope.listSelectSearch=[];
           $scope.modelParamL.selTotalCount=0;
           $scope.modelParamL.selTotalCount_regist=0;
           $scope.modelParamL.selTotalCount_potient=0;
           $scope.isClickShowCheck=false;
           commonL.checkAll(false);
      },
      //  删除单个移交客户
      removeCheckOne:function(record){
            var model = $scope.listSelectSearch.splice($scope.listSelectSearch.indexOf(record), 1)[0];
            console.log(model);
            $scope.modelParamL.selTotalCount=$scope.listSelectSearch.length;
            this.pushCacheCheck(false,model);
      },
      //  获取混存移交客户列表
      getCacheCheckList:function(){
            $scope.isClickShowCheck=true;
            $scope.listSelectSearch=localStorage.get("$listSelectSearch");
      },
      //  点击返回操作
      getBack:function(){
          $scope.isClickShowCheck=false;
          this.getList();
      },
      //  获取移交参数列表 
      getTransformParam:function(){
            var cacheList=localStorage.get("$listSelectSearch"),
                arrCIDS=[],
                arrPIDS=[];
            cacheList.filter(function(item){
                 if(item.customerType==2)
                    arrCIDS.push(item.id);
                 else if(item.customerType==1)
                     arrPIDS.push(item.id);
                 
            })
       
            var _postParamModel = {
                "receiverType": $scope.modelParamL._seaType_.code,  // 接收人类型
                "userId": $scope.modelS.inputId,        // 销售ID
                "cids": arrCIDS,   // 注册客户
                "pcids": arrPIDS
            }
            return _postParamModel;
      },
      reLoadTransformCount:function(){
          $scope.modelS.selClickModel.leftover_customer =  parseInt($scope.modelS.selClickModel.leftover_customer) - parseInt($scope.modelParamL.selTotalCount_regist);
          $scope.modelS.selClickModel.leftover_potential_customer = parseInt($scope.modelS.selClickModel.leftover_potential_customer) - parseInt($scope.modelParamL.selTotalCount_potient);
      }, 
      //  开始移交操作
      startTransform:function(){
            if($scope.modelParamL.selTotalCount<=0){ // 是否已选移交客户
                dialog.tips({ 
                  actionText: '确定' ,
                  bodyText: '请您先勾选移交客户!',
                });
                return;
            }else if(parseInt($scope.modelS.selClickModel.leftover_customer)<0  ||  parseInt($scope.modelS.selClickModel.leftover_potential_customer)<0){  // 是否超过客余容量
                dialog.tips({ 
                    actionText: '确定' ,
                    bodyText: '超出客余容量不允许移交操作!',
                });
                return;
            }else if($scope.isBeyoundMaxCheck2 ){  // 是否超过(潜在客户)客余容量
                dialog.tips({ 
                    actionText: '确定' ,
                    bodyText: '潜在客户客余容量超出了最大限制，无法移交!',
                });
                return;
            }else if($scope.isBeyoundMaxCheck1 ){  // 是否超过客余(注册客户)容量
                dialog.tips({ 
                    actionText: '确定' ,
                    bodyText: '注册客户客余容量超出了最大限制，无法移交!',
                });
                return;
            }
            
            var _postParamModel = this.getTransformParam();
            dialog.tips({
                actionText: '确定' ,
                bodyText: '你确定移交客户?',
                ok: function() {
                  req.getdata('/customer/set_sales', 'POST', function(data) {
                    if(data.status == 0) {
                      dialog.tips({bodyText:'客户移交成功'});
                      // 重新加载刷新客户容量
                      commonL.reLoadTransformCount();
                      // 刷新列表
                      commonL.getList();
                      // 刷新数量
                      commonL.removeCheckAll();
                    }else{
                      dialog.tips({bodyText:'客户移交失败！'});
                    }
                  }, _postParamModel);
                }
            });
      },
      //  初始化参数
      initParam:function(){
            $scope.modelParamL={
               first_order:"",
               system:"",
               city:"",
               sale_role:"",
               sale_name:"",
               type:"",
               line:"",
               status:"",
               searchValue:"",
               orderRecord:"",
               selTotalCount_regist:0,
               selTotalCount_potient:0
            }
            $scope.modelL={statusModel:false,inputModelL:"",inputId:-1,dropList:[],selClickItem:false};

            $scope.modelParamL.selTotalCount=0;
            $scope.listSearch=[];
            $scope.saleNameModelL=[];
            $scope.statusModelL=[];
      },
      //  同步设置,移交客户的所属，以及城市等参数
      setSystemAndCity:function(_system,_city,_seaType,_type){
           $scope.show_list=true;
           $scope.modelParamL.system=_system;
           $scope.modelParamL.city=_city;
           $scope.modelParamL._type_=_type;
           $scope.modelParamL._seaType_=_seaType;
           $scope.lineModelL = $filter('filter')( $scope.lines,{location_id:_city.id,site_src:_system.id});
           $scope.saleRoleModelL =  $filter('filter')($scope.saleRoleModelLC,{sid:_seaType.code});
           $scope.statusModelL=[];
      },
      //  销售姓名筛选操作
      selectDropSaleName:function(){

            $scope.keyFunL=function($event) {
               $event.preventDefault();
               $event.stopPropagation();
               $scope.modelL.statusModel=true;
               $scope.modelL.changeOther=false;
            }

            $scope.clickItemL=function(_value,_id,_model) {
               if(_value=="姓名没有找到"){
                   return;
               }
               $scope.modelL.selClickItem=true;
               $scope.modelL.statusModel=false;
               $scope.modelL.inputModelL=_value;
               $scope.modelL.changeOther=true;
               $scope.modelL.inputId=_id;
            }
           
            $scope.$watch("modelL.inputModelL",function(_new,_old){
                _new=_new||"";

                var _city=$scope.modelParamL.city;
                
                var dropList= [];

                if(_city!=null && _city!="") {
                        $scope.$emit("updateSaleNameL",$scope.modelParamL.city);
                        dropList=$scope.saleNameModelL;
                }

                $scope.modelL.statusModel=true;

                // 过滤
                var model=  $filter('filter')(dropList,{name:_new});

                if(model.length>0 && $scope.modelL.selClickItem==true && _new!=""){
                  
                }else{
                   $scope.modelL.selClickItem=false;
                   $scope.modelL.inputId=-1;
  
                }
              
                if(model.length==0){
                   $scope.saleNameModelL=[{name:"姓名没有找到"}];
                }else {
                   $scope.saleNameModelL=model;
                }
            })
           
      },
      //  初始化加载参数列表
      initLoadRequest:function(){
            req.getdata('/customer/list_transfer_options', 'POST', function(data) {
                    if(data.status == 0) {
                      $scope.cityModelG = $scope.cityModelS = $scope.cities = data.cities;
                      $scope.customer_types = data.customer_types;
                      $scope.order_records=data.order_record;
                      $scope.lines = data.lines;
                      $scope.saleRoleModelS = $scope.recvroles = data.recvroles;
                      $scope.saleNameModelS =  $scope.salesList = $scope.salesListL= data.sales;
                      $scope.saleRoleModelL = $scope.saleRoleModelLC  = data.saleroles;
                      $scope.systemModelG = $scope.systemModelS =  $scope.sites = data.sites;
                    }
            });
      },
      init:function(){
              this.initParam();
              this.initLoadRequest();
              this.pagerInitSetting();
              
              // 已选的移交客户总数
              $scope.modelParamL.selTotalCount=0;

              // 是否超过最大客余容量
              $scope.isBeyoundMaxCheck=false;

              $scope.saleRoleModelL=[];
              $scope.lineModelL=[];
              $scope.statusModelL=[];
              $scope.disabledL=false;
              $scope.typeModelL=[];
              
              $scope.first_orderList=[{name:"是",id:1},{name:"否",id:0}];

              this.selectDropSaleName();
             
              
              // 发布，销售姓名，事件更新操作
              $scope.$on("updateSaleNameL",function(event,_city){
                  var _filterModel=$scope.salesList;
                      
                  if(_city!="" && _city!=null) {
                     _filterModel = $filter("filter")(_filterModel,{ province_id:_city.id});
                  }

                  $scope.saleNameModelL=_filterModel;
      
                  $scope.saleNameModelL=$scope.saleNameModelL.filter(function(item){
                           return  item.id!=$scope.modelS.inputId;
                  })

                  var filterChangeModel= $scope.saleNameModelL.filter(function(item){
                           return  item.id!=$scope.modelL.inputId;
                  })

                  if(filterChangeModel.length===0){
                      $scope.modelL.changeOther=true;
                      $scope.modelL.inputModelL="";
                      $scope.modelL.statusModel=false;
                      $scope.modelL.selClickItem=false;
                  }
              })

              $scope.$watch('modelParamL.city',function(_new,_old){

                    if(_new!="")
                      $scope.$emit("updateSaleNameL",_new);
              })

              $scope.$watch('modelS.inputId',function(_new,_old){
                    if(_new!=-1){
                      $scope.$emit("updateSaleNameL",$scope.modelParamL.city);
                    }
              })

              $scope.$watch('modelParamL.selTotalCount_regist',function(param1,param2){
                    $scope.tipBackground1={"background":"#777777"};

                    if($scope.modelParamL._type_=="g"){
                        $scope.isBeyoundMaxCheck1=false;

                    }else if($scope.modelParamL._type_=="s"){
                        if( parseInt($scope.modelParamL.selTotalCount_regist) > parseInt($scope.modelS.selClickModel.leftover_customer)){
                           $scope.tipBackground1={"background":"red"};
                           $scope.isBeyoundMaxCheck1=true;
                        } else{
                           $scope.isBeyoundMaxCheck1=false;
                        }
                    }
              })

              $scope.$watch('modelParamL.selTotalCount_potient',function(param1,param2){
                    $scope.tipBackground2={"background":"#777777"};
                    console.log("potential:"+ parseInt($scope.modelParamL.selTotalCount_potient) +","+parseInt($scope.modelS.selClickModel.leftover_potential_customer));
                    if($scope.modelParamL._type_=="g"){
                        $scope.isBeyoundMaxCheck2=false;

                    }else if($scope.modelParamL._type_=="s"){
                        if( parseInt($scope.modelParamL.selTotalCount_potient) > parseInt($scope.modelS.selClickModel.leftover_potential_customer)){
                           $scope.tipBackground2={"background":"red"};
                           $scope.isBeyoundMaxCheck2=true;
                        } else{
                           $scope.isBeyoundMaxCheck2=false;
                        }
                    }
              })
      }
  }
  
  commonGongHai.init();
  commonSiHai.init();
  commonL.init();
  
  $scope.gong=commonGongHai;
  $scope.si=commonSiHai;
  $scope.com=commonL;
}]);