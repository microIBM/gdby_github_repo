'use strict';
// create by liwei 2015/8/25
angular
    .module('dachuwang')
    // 用户拜访修改
    .controller('VisitModifyCtrl', ['$scope', 'rpc', '$cookieStore', 'daChuDialog', '$state', '$filter', function($scope, req, $cookieStore, dialog, $state, $filter) {
        var common = {
            init: function() {
                this.param();
                this.scope();
            },
            param: function() {
                $scope.updateBeforeDate=$state.params.date;
            },
            request: {
                modify: function(dateInt) {
                    req.load("customer_visit/update_visit_date", "post", {
                        visit_id: $state.params.vid,
                        visit_date: dateInt
                    }).then(function(data) {
                        console.log(data);
                    });
                }
            },
            scope: function() {
                var func = function() {
                    var cellDate = this;
                    if(cellDate==undefined) return;

                    // 点击单元格日期,转成时间戳
                    cellDate = $filter("date")(cellDate, "yyyy-MM-dd HH:mm");
                    //cellDate = (+new Date(cellDate)) / 1000;

                    // 当天日期转成时间戳
                    var todayDate = $filter("date")(new Date(), "yyyy-MM-dd");
                    //todayDate = (+new Date(todayDate)) / 1000;

                    if(cellDate<todayDate){
                         dialog.tips({
                            bodyText: "修改日期不能小于当天日期!",
                            actionText: "确定",
                        });
                         return;
                    }

                    dialog.tips({
                        bodyText: "确定修改日期为：" + cellDate,
                        actionText: "确定",
                        ok: function() {
                            var timeStampStr = (+new Date(cellDate))/1000;
                            common.request.modify(timeStampStr);
                            $state.go("page.visit");
                        }
                    });
                }

                // 单元格点击事件
                $scope.cellClick = function() {
                    return func;
                }
            }
        }

        common.init();

    }]);