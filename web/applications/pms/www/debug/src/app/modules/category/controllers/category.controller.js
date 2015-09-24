'use strict';
// 分类列表
angular.module('hop')
 .controller('CategoryCtrl', ['$scope', 'dialog', 'req', 'daChuLocal', 'appConfigure', function($scope,  dialog, req, daChuLocal, appConfigure) {

   $scope.title = '分类列表';
   $scope.upId = 0;
   $scope.site_url = appConfigure.url;
   function topCate() {
     $scope.list = [];
     req.getdata('category/get_category_list', 'POST', function(data) {
       angular.forEach(data.list, function(item){
         $scope.list.push(item);
       });
       $scope.reBackbtn = false;
     });
   }
   topCate();
   // 编辑
   $scope.edit = function(index) {
     req.redirect('/category/edit/' + $scope.list[index].id);
   };
   daChuLocal.set('his_path',[]);
   // 二级以下的分类
   function childList(id) {
     req.getdata('category/get_category_list', 'POST', function(data) {
       if(data.status === 0 && data.list.length > 0) {
         $scope.list = [];
         angular.forEach(data.list, function(item){
           $scope.list.push(item);
         });
         $scope.showRebackbtn = true;
       } else {
         alert('没有下级节点了');
       }
     },{upid:id});
   }
   // 查看子类
   $scope.getChild = function(index) {
     var topId = $scope.list[index].id;
     var arr = daChuLocal.get('his_path');
     arr.push($scope.list[index].id);
     daChuLocal.set('his_path', arr);
     childList(topId);
   }
   // 查看父类
   $scope.getParent = function(index) {
     var arr = daChuLocal.get('his_path');
     if(arr.length >= 1) {
       arr.pop();
       daChuLocal.set('his_path', arr);
     }
     var topId = (arr.length>=1 ? arr[arr.length-1] : 0);
     childList(topId);
   }
   // 返回上级菜单
   $scope.reback = function() {
     var lastId = daChuLocal.get('cateListLastId');
     var lstIds = lastId.split('_');
     if(lstIds.length > 2) {
       lstIds.pop();
       var lstId = lstIds.pop();
       // daChuLocal.set('cateListLastId', lstIds.join('_'));
       childList(lstId);
     } else {
       $scope.showRebackbtn = false;
       topCate();
     }
   }
   $scope.setStatus = function($index, status) {
     var cateId = $scope.list[$index].id;
     if(parseInt(status) === 0) {
       dialog.tips({
         headerText: '请谨慎操作',
         bodyText:  '确定要禁用' + $scope.list[$index].name + '?',
         actionText: '确定',
         ok: function() {
           // 执行逻辑删除
           // $scope.list.splice($index, 1);
           req.getdata('category/del', 'POST', function(data) {
             if(parseInt(data.status) === 0) {
               $scope.list[$index].status = 0;
               dialog.tips({bodyText:data.msg});
             } else {
               dialog.tips({bodyText:'禁用失败，请联系管理人员'});
             }
           },{id:cateId});
         }
       });
     } else {
       dialog.tips(
         {
         headerText: '启用被禁用的分类',
         bodyText: '确定要启用' + $scope.list[$index].name + '?',
         actionText: '确定',
         ok: function() {
           req.getdata('category/reuse', 'POST', function(data) {
             if(parseInt(data.status) === 0) {
               $scope.list[$index].status = 1;
               dialog.tips({bodyText:data.msg});
             } else {
               dialog.tips({bodyText:'启用失败，请联系管理员'});
             }
           }, {id:cateId});
         }
       }
       );
     }
   }
   $scope.back = function() {
     req.redirect('/category');
   }

   // 分页参数初始化
   $scope.paginationConf = {
     currentPage: 1,
     itemsPerPage: 15
   };
   // 通过$watch currentPage和itemperPage 当他们一变化的时候，重新获取数据条目
   $scope.$watch('paginationConf.currentPage + paginationConf.itemsPerPage', function() {});


 }]);
