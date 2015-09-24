'use strict';
// create by liwei 2015/8/21
angular
    .module('dachuwang')
    // 用户编辑
    .controller('VisitCustomerCtrl', ['$scope', 'rpc', '$cookieStore', 'daChuDialog', '$state','daChuLocal','Analysis','$filter', function($scope, req, $cookieStore, dialog, $state, daChuLocal, Analysis, $filter) {

        var common = {
            init: function() {
                this.param();
                this.request.list();
                this.scope();
            },
            param: function() {
                $scope.focusArr = [];
                $scope.suggestsArr = [];
                $scope.remark = "";
                $scope.focusIDS = [],
                $scope.suggestIDS = [];
                $scope.stateVid=$state.params.vid;
            },
            request: {
                setCheckBox:function(){
                    $scope.focusIDS = [];
                    $scope.suggestIDS = [];

                    $scope.focusArr.filter(function(item) {
                        $scope.focusIDS.push(item.id);
                    })
                    $scope.suggestsArr.filter(function(item) {
                        $scope.suggestIDS.push(item.id);
                    })
                },
                list: function() {
                    var localModel= daChuLocal.get("visit_add_date");

                    req.load("customer_visit/for_visit", "post", {
                        user_id:$state.params.uid ,
                        visit_id:$state.params.vid,
                        is_potential:$state.params.pid,
                    }).then(function(json) {
                        $scope.info = json.data;

                        // 添加记录的时间赋值操作
                        if($state.params.vid ===0){
                            $scope.info.visit_date=localModel.visitDate;
                        }

                        $scope.focusCategories = json.focus_categories;
                        $scope.suggestions = json.suggestions;
                    }, function(res) {
                        console.log(res);
                    })
                },
                create:function(){
                    common.request.setCheckBox();

                    var date = $filter("date")($scope.info.visit_date, "yyyy-MM-dd HH:mm");
                    
                    date = (+new Date(date)/1000);
          
                    var postData = {
                        "type":1,
                        "visit_date":date,
                        "user_id": $state.params.uid,
                        "is_potential":$state.params.pid,
                        "focus_category": $scope.focusIDS.join(','),
                        "suggestion_type": $scope.suggestIDS.join(','),
                        "remarks": $scope.remark
                    }
                    Analysis.send('客户拜访',{kind:'进店拜访提交'});
                    req.load("customer_visit/create", "post", postData).then(function(data) {
                        daChuLocal.remove("visit_add_date");
                        $state.go("page.visit");
                    }, function(res) {
                        common.alert(res);
                    })
                },
                update: function() {
                    common.request.setCheckBox();
                    
                    var postData = {
                        "visit_id": $state.params.vid,
                        "focus_category": $scope.focusIDS.join(','),
                        "suggestion_type": $scope.suggestIDS.join(','),
                        "remarks": $scope.remark,
                        'is_potential':$state.params.pid
                    }

                    req.load("customer_visit/update", "post", postData).then(function(data) {
                        $state.go("page.visit");
                    }, function(res) {
                        common.alert(res);
                    })
                }
            },
            alert: function(title) {
                dialog.tips({
                    bodyText: title,
                    actionText: "确定",
                    ok: function() {
                        return;
                    }
                });
                return;
            },
            setArr: function(model, arr) {
                if (arr.indexOf(model) > -1) {
                    arr.splice(arr.indexOf(model), 1);
                } else {
                    arr.push(model);
                }
            },
            scope: function() {
                // 点击复选框
                $scope.checkClick = function(model, flag) {
                    if (flag == 1) {
                        common.setArr(model, $scope.focusArr)
                    } else if (flag == 2) {
                        common.setArr(model, $scope.suggestsArr)
                    }
                }

                // 创建拜访记录
                $scope.create = function() {
                    common.request.create();
                }


                $scope.reset=function(){
                   $scope.focusCategories.filter(function(item){
                      item.checked=false;
                   })

                   $scope.suggestions.filter(function(item){
                      item.checked=false;
                   })
                   common.param();
                }


                // 更新拜访计划
                $scope.update = function() {
                    if ($scope.focusArr.length == 0) {
                        common.alert("请先选择客户关注品类");
                        return;
                    } else if ($scope.suggestsArr.length == 0) {
                        common.alert("请先选择意见类型");
                        return;
                    }

                    common.request.update();
                }
            }
        }

        common.init();

    }]);
