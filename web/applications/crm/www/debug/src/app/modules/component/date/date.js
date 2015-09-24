'use strict';

angular.module('dachuwang').directive('mdDate', function($compile) {
	return {
		restrict: 'EA',
		scope: {
			tdCallBack: '=tdCallBack',
			listTags: '=listTags',
			isNormal: '@isNormal'
		},
		transclude: true,
		replace: true,
		link: function(scope, element, attrs) {
			element.prepend('<style type="text/css">.mdCurrentDate{font-weight:bold;color:#ff6601;letter-spacing:2px;}.td_bg_hover{color:#009688;font-weight:bold;}#templateCustomerDate{margin:10px 24px;} #templateCustomerDate table td{border:1px solid #f3f3f3;background:white;;text-align:center}#templateCustomerDate table thead tr td {font-weight:bold;color:#009688;} #templateCustomerDate table tr td{position:relative;line-height:40px;}#templateCustomerDate table tr:nth-child(even) td{background-color:none;}#templateCustomerDate ul{margin:0 auto;padding:0px;text-align:center;}#templateCustomerDate ul li{list-style-type:none;display:inline-block;padding:5px 20px;margin:10px 0;}#templateCustomerDate ul>li:nth-child(1),#templateCustomerDate ul>li:nth-last-child(1){ color:white;background:#009688}.triangle-topright-blue {width: 0;height: 0;border-top: 18px solid #5bc0de;border-left: 18px solid transparent;right:0;top:0;position:absolute;}.triangle-topright-red {width: 0;height: 0;border-top: 18px solid red;border-left: 18px solid transparent;right:0;top:0;position:absolute;}<style>');
			var createDate = {
				// 当前月
				currentMonth: 0,
				// 当前年
				currentYear: 0,
				// 初始化操作
				init: function() {
					this.currentMonth = new Date().getMonth();
					this.currentYear = new Date().getFullYear();
					return this.ShowMonthDetail(new Date().getFullYear(), new Date().getMonth() + 1);
				},
				// 显示月列表
				ShowMonthDetail: function(Year, Month) {
					scope.currentMonth = Month;
					scope.currentYear = Year;

					return this.monthCreate(Year, Month, 20);
				},
				// 生成月列表
				monthCreate: function(Year, Month, data) {
					//生成指定月的明细
					var ary = ['<table cellpadding="0" cellspacing="0" width="100%" border="1" style="border-collapse: collapse;">', '<thead><tr> <td class="timer_td1"> 日</td><td> 一</td><td> 二</td><td>三 </td> <td>四</td><td>五</td><td>六</td></tr></thead>', '<tbody>'];
					var dtFirstDay = new Date(Year, Month - 1, 1);
					//var weekDay = ["星期天", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六"];
					var weeknum = dtFirstDay.getDay();
					if (weeknum > 0) {
						ary[ary.length] = new Array(weeknum + 1).join('<td class="td_bg_none"></td>');
					}

					var dayNum = new Date(Year, Month, 0).getDate();

					var curDate = new Date();
					var curDayNum = -1;
					if (curDate.getFullYear() === dtFirstDay.getFullYear() && curDate.getMonth() == dtFirstDay.getMonth()) {
						curDayNum = curDate.getDate();
					}
					var aryflag = this.ToMonthAry(data);
					var strClass = "";
					Month = (Month > 0 && Month <= 9) ? "0" + Month : Month;

					for (var i = 1; i <= dayNum; ++i) {
						var day = (i > 0 && i <= 9) ? "0" + i : i;
						var splitDate = Year + "-" + Month + "-" + day;

						if (weeknum == 0) {
							ary[ary.length] = '<tr>';
						}

						// 非普通日历,定制化需求
						if (scope.isNormal == 0) {
							var curentItem = "";
              
              // 筛选出当前月份的拜访日期
              if(scope.listTags!=undefined){
                 scope.listTags.filter(function(item) {
							    	if (item.date == splitDate) {
								     	curentItem = item;
							    	}
							   })
              }
              
							if (curentItem.value == 1 && splitDate == curentItem.date && curDayNum === i) { //当天
								ary[ary.length] = '<td data-date=' + splitDate + ' class="td_bg_hover">' + i + '<span class="triangle-topright-red"></span></td>';

							} else if (curentItem.value == 1 && splitDate == curentItem.date) {
								ary[ary.length] = '<td data-date=' + splitDate + '>' + i + '<span class="triangle-topright-red"></span></td>';

							} else if (curentItem.value == 0 && splitDate == curentItem.date && curDayNum  === i) { //当天
								ary[ary.length] = '<td data-date=' + splitDate + ' class="td_bg_hover">' + i + '<span class="triangle-topright-blue"></span></td>';

							} else if (curentItem.value == 0 && splitDate == curentItem.date) {
								ary[ary.length] = '<td data-date=' + splitDate + '>' + i + '<span class="triangle-topright-blue"></span></td>';

							} else {
								if (curDayNum === i) {
									strClass = "td_bg_hover";
								} else {
									strClass = "td_bg_none";
								}
								ary[ary.length] = '<td data-date=' + splitDate + ' class=' + strClass + '>' + i + '</td>';
							}

							// 普通日历
						} else if (scope.isNormal == 1) {
							//是否为当天
							if (aryflag[i] === true) { //签到
								ary[ary.length] = '<td data-date=' + splitDate + '>' + i + '</td>';
							} else {
								if (curDayNum === i) {
									strClass = "td_bg_hover";
								} else {
									strClass = "td_bg_none";
								}
								ary[ary.length] = '<td data-date=' + splitDate + ' class="tdDay ' + strClass + '">' + i + '</td>';
							}
						}

						if (weeknum == 6) {
							ary[ary.length] = '</tr>';
							weeknum = 0;
						} else {
							++weeknum;
						}
					}
					if (weeknum > 0) {
						ary[ary.length] = new Array(8 - weeknum).join('<td class="td_bg_none"></td>');
						ary[ary.length] = '</tr>';
					}
					ary[ary.length] = '</tbody>';
					ary[ary.length] = '</table>';
					var html = ary.join('');

					// 清除table
					element.find("table").remove();

					// 编译加入到scope的作用域中
					var compileHtml = $compile(html)(scope);

					// 添加到页面
					element.append(compileHtml);

					element.find("td").on("click", function() {
						var valueDate = angular.element(this).attr("data-date");
						if (typeof scope.tdCallBack == "function")
							scope.tdCallBack.apply(valueDate);
						// if (scope.tdCallBack && angular.element(this).hasClass("tdDay") && angular.element(this).find("span").length == 0)
						// 	angular.element(this).append("<span class='triangle-topright'></span>");
						// else
						// 	angular.element(this).find("span").remove();
					})
					return html;
				},
				// 上月
				lastMonth: function() {
					if (this.currentMonth === 0) {
						this.currentMonth = 12;
						this.currentYear = this.currentYear - 1;
					}

					this.currentMonth = this.currentMonth - 1;
					this.ShowMonthDetail(this.currentYear, this.currentMonth + 1);

				},
				// 下月
				nextMonth: function() {
					if (this.currentMonth === 11) {
						this.currentMonth = 0;
						this.currentYear = this.currentYear + 1;
					} else {
						this.currentMonth = this.currentMonth + 1;
					}

					this.ShowMonthDetail(this.currentYear, this.currentMonth + 1);

				},
				// 
				ToMonthAry: function(data) {
					var ary_list = new Array(32);
					for (var i = 0, len = data.length; i < len; ++i) {
						ary_list[data[i]] = true;
					}
					return ary_list;
				}
			}

			// 创建日期组件操作
			var htmlDateTable = createDate.init();

			// 上月
			scope.last = function() {
				createDate.lastMonth();
			}

			//下月
			scope.next = function() {
				createDate.nextMonth();
			}
		},
		template: '<div id="templateCustomerDate">' +
			'<div>' +
			'<ul><li ng-click="last()">上月</li><li class="mdCurrentDate">{{currentYear}}年{{currentMonth}}月</li><li ng-click="next()">下月</li></ul>' +
			'</div>' +
			'<div ng-transclude></div>' +
			'</div>'
	}
});
