'use strict';
// create by liwei 2015/8/21
angular
    .module('dachuwang')
    // 用户编辑
    .controller('VisitAddCustomerCtrl', ['$scope', 'rpc', '$cookieStore', 'daChuDialog', '$state', '$filter','pagination', function($scope, req, $cookieStore, daChuDialog, $state, $filter, pagination) {

        var common = {
            init: function() {
                this.param();
                this.scope();
                this.initPage();
               
            },
            param: function() {
                $scope.vm = {
                    searchKey: "",
                    searchList: []
                }
            },
            initPage:function(){
               $scope.dateList = [];
               $scope.pagination = pagination;
               $scope.pagination.perPage = 15;
               $scope.pagination.init(common.request.list);
               //$scope.pagination.nextPage();
            },
            request: {
                list: function(callback) {
                    

                    var postData = {
                         "currentPage": pagination.page,
                         "itemsPerPage": $scope.pagination.perPage
                    }

                    req.load("customer_visit/get_private_sea", "post", postData).then(function(data) {
                        $scope.dateList = $scope.dateList.concat(data.list);
                        $scope.vm.searchList = $scope.dateList;
                           
                        if(data.list.length < $scope.pagination.perPage){
                           callback(true);
                        }else {
                           callback(false);
                        }
                    }, function(res) {
                        console.log(res);
                    })
                }
            },
            scope: function() {
                $scope.search = function() {
                    if ($scope.vm.searchKey.length > 0) {
                        var list = $filter("filter")($scope.dateList, {
                            shop_name: $scope.vm.searchKey
                        });
                        $scope.vm.searchList = list;

                    } else {
                        $scope.vm.searchList = $scope.dateList;

                    }
                }

                $scope.selectItem = function(item) {
                    $state.go("page.visit-add", {
                        shop: encodeURIComponent(item.shop_name + "-" + item.id+"-"+ item.is_potential)
                    });
                }

            }
        }

        common.init();

    }]);