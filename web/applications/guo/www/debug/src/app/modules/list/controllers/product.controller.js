'use strict';

angular
  .module('dachuwang')
  .controller('ProductCateCtrl', ['$scope', '$element', '$cookieStore', '$location','$interval', 'req', 'daChuDialog', 'pagination', 'daChuLocal', 'daChuTimer', 'cartlist', function($scope, $element, $cookieStore, $location, $interval, req, daChuDialog, pagination, daChuLocal, daChuTimer, cartlist) {
  // 产品列表以及购买
  $scope.local = daChuLocal;
  $scope.items = [];
  $scope.cartlist = cartlist;
  var type = $cookieStore.get('type');
  if(type == undefined) {
    type = -1;
  }
  $scope.style = type;
 // 添加购物车
  $scope.addItems = function(item) {
    if(cartlist.ids.indexOf(item.id) >= 0) {
      cartlist.changeItem(item, -1, 0);
    } else {
      cartlist.changeItem(item);
    }
  };
  $scope.cateIndex = 0;
  $scope.doc = document.documentElement;
  $scope.menubar = {
    itemWidth: [],
    totalWidth: 0,
    left: 0,
    prev: 0,
    setLeft: function(index) {
      if(index > 1) {
        var newLeft = 0, $this = this;
        angular.forEach($scope.cates, function(item, idx) {
          if(idx < index - 1 && $this.totalWidth - newLeft > $scope.doc.clientWidth) {
            newLeft += $scope.menubar.itemWidth[idx];
          } else if (idx == index - 1) {
            newLeft += parseInt($scope.menubar.itemWidth[idx] / 4);
          }
        });
        $scope.menubar.left = -newLeft;
      } else if(index <= 1) {
        $scope.menubar.left = 0;
      }
      this.prev = index;
    },
    init: function() {
      angular.forEach($scope.cates, function(item, idx){
        $scope.menubar.itemWidth[idx] = item.name.length * 23 + 20;
        $scope.menubar.totalWidth += $scope.menubar.itemWidth[idx];
      });
    }
  };
  // 获取菜品

  // 加载分页
  $scope.pagination = pagination;

  // 获取产品
  $scope.getProduct = function(childId) {
    $scope.showCate = false;
    // 检查缓存是否存在
    var cityId = daChuLocal.get('cityId'),
        marketId = daChuLocal.get('marketId'),
        localPage = daChuLocal.get('page_' + childId + '_' + marketId),
        localItems = daChuLocal.get('product_' + childId + '_' + marketId),
        localAddress = daChuLocal.get('local_address'),
        localTimeout = daChuLocal.get('product_' + childId + '_time_' + marketId ),
        url = 'product/lists/';
    url += cityId || 1;
    if(marketId) {
      url += '/' + marketId;
    }
    $scope.productCateId = childId;
    // 查到缓存，直接读取
    // 根据path来获取
    var path = daChuLocal.get('path') || '',
    childIndex =0;
    if(path) {
      angular.forEach($scope.childCates, function(cate, key) {
        if(path.indexOf(cate['path']) !== -1) {
          childIndex = key;
        }
      })
       $scope.childId = $scope.childCates[childIndex].id;
    }
    if(localItems && daChuTimer.compare(localTimeout)) {
      $scope.items = localItems;
      if(localPage) {
        pagination.page = parseInt(localPage);
      }
    } else { // 查不到缓存，则先初始化空数据
      $scope.items = [];
      $scope.address = "";
      pagination.page = 0;
    }
    if(localAddress && daChuTimer.compare(localTimeout)) {
      $scope.address = localAddress;
    }
    // 初始化分页回调
    pagination.init(function(callback) {
      req.getdata(url, 'POST', function(data) {
        daChuLocal.set('local_address', data.info.address);
        $scope.address = data.info.address;
        angular.forEach(data.list, function(product){
          $scope.items.push(product);
        });
        if(callback) {
          callback(!data.list.length);
          if(data.list.length) {
            daChuLocal.set("product_" + childId + '_' + marketId, $scope.items);
            daChuLocal.set('page_' + childId + '_' + marketId, pagination.page);
            daChuLocal.set('product_' + childId + '_time_' + marketId, daChuTimer.getNow());
          }
          if($scope.items.length == 0) {
            $scope.text = '暂无此类货物';
          }
        }
      }, {
        page: pagination.page,
        upid: childId
      });
    });

    // 重置分页号
    if(localItems && daChuTimer.compare(localTimeout)) {
      if(localPage) {
        pagination.page = parseInt(localPage);
      }
    } else {
      pagination.page = 0;
    }
    pagination.nextPage();
  };
 
  $scope.changeIndex = function(pos) {
    if(!$scope.cates[$scope.cateIndex + pos]) {
      return;
    }
    $scope.cateIndex = $scope.cateIndex + pos;
    $scope.getChild($scope.cateIndex);
  };
  // 设置默认
  $scope.setDefault = function(data) {
    // 顶级分类
    $scope.cates = data.top;
    $scope.child = data.child;
    // 二级分类
    $scope.secondCate = data.second;
    // 三级分类
    $scope.secondCateChild = data.second_child;
    $scope.menubar.init();
    // 获取三级分类
    if($scope.cates && $scope.cates.length > 0) {
      var path = daChuLocal.get('path') || '',
      index = 0;
      if(path) {
        angular.forEach($scope.cates, function(cate, key) {
          if(path.indexOf(cate['path']) !== -1) {
            index = key;
          }
        })
      }
      $scope.cates[index].cls = 'active';
      $scope.getChild(index);
    }
  }
  // 获取三级类
  $scope.getChild = function(index) {
    $scope.topId = $scope.cates[index].id;
    $scope.cateIndex = index;
    // 改变导航标签显示位置
    $scope.menubar.setLeft(index);
    $scope.childCates = $scope.secondCate[$scope.topId];
    // 根据path来获取
    var path = daChuLocal.get('path') || '',
    childIndex = 0;
    if(path) {
      angular.forEach($scope.childCates, function(cate, key) {
        if(path.indexOf(cate['path']) !== -1) {
          childIndex = key;
        }
      })
    }
    $scope.childId = $scope.childCates[childIndex].id;
    // cate 为1 显示分类
    // 设置当前分类的length
    $scope.childLength = $scope.childCates.length;
    if($scope.childCates.length == 1) {
      $scope.childCates = $scope.child[$scope.topId];
    }
    // 二级分类多个
    $scope.getThird(childIndex);
  };
  // 加载三级分类
  $scope.getThird = function(index) {
    if(!$scope.childCates[index]) {
      return;
    }
    daChuLocal.set('path', $scope.childCates[index].path);
    $scope.secondId = $scope.childCates[index].id;
    // 显示分类
    $scope.showCate = $scope.childLength > 1? true : false;
    if($scope.showCate) {
      $scope.items = $scope.secondCateChild[$scope.secondId];
    } else {
      $scope.getProduct($scope.secondId);
    }
    $scope.childId = $scope.childCates[index].id;
  }
  // 获取产品list
  if($location.$$path === '/' || $location.$$path.indexOf('/product/lists') === 0) {
    // 判断是否已经有了，有的话就直接从localStorage
    // 每个分类产品存储上一次最后一页
    var cateData = daChuLocal.get('cateList');
    var cateDataTime = daChuLocal.get('cateListTime');
    if(!cateData ||!daChuTimer.compare(cateDataTime)) {
      req.getdata('category/lists', 'GET', function(data) {
        // 需要将所有的分类设置，然后设置当前时间，然后更具缓存来判断
        $scope.local.set('cateList', data.list);
        $scope.local.set('cateListTime', daChuTimer.getNow());
        $scope.setDefault(data.list);
      });
    } else {
      pagination.isProcessing = false;
      $scope.setDefault(cateData);
    }
  };
  // 选择城市以及market
  $scope.citySelect = function() {
    req.getdata('user/register_options', 'GET', function(data) {
      var cities = data.list.cities;
      var markets = data.list.market;
      daChuDialog.tips(
        {
          headerText: '选择市场',
          bodyText: cities,
          markets: [],
          cityChange: function(city) {
            this.markets = markets[city.id];
          },
          actionText:'确定',
          ok:function(city, market) {
            if(city) {
              var cityId = city.id;
              var marketId = 0;
              if(market == null || market === undefined) {
                market= this.markets[0];
              }
              marketId = market.id;
              // 设置local
              daChuLocal.set('marketId', marketId);
              daChuLocal.set('cityId', cityId);
              daChuLocal.set('cityMarketTime', daChuTimer.getNow());
            }
            // 清空缓存
            daChuLocal.set('product_' + $scope.childId , '');
            daChuLocal.set('local_address', city.name +  ' ' +market.name);
            $scope.getProduct($scope.productCateId);
          },
          closeText:'取消'
        }
      );
    });
  }
}]);
