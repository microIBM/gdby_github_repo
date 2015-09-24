'use strict';
// 用户权限控制
angular
  .module('hop')
  .factory('HopPermit',['req', function(req) {
  // 获取权限列表存储localStorage
  // 几种用户类型
  var userTypes = [100,10,11,12,103];
  // 权限控制
  var Permits = {
    menus:{
      
    },
    checkType: function(userType) {
      if(userTypes.indexOf(userType) !== -1) {
        return true;
      }
      return false;
    }
  };
  
  // 检测授权
  return {
    getPermit: function(userType) {
      if(Permits.checkType(userType)) {
        return true;
      } 
      return false;
    }
  }
}])
// 管理权限
.factory('HopAuth', ['HopPermit', '$cookieStore', 'req', 'dialog', function(HopPermit, $cookieStore, req, dialog) {
  var user = {
    isLogin: false,
    getAuth: function() {
      var type = parseInt($cookieStore.get('type'));
      if(HopPermit.getPermit(type)) {
        this.isLogin = true;
        return true;
      } else {
        this.isLogin = false;
        return false;
      }
    },
    changePwd: function(uid) {
      // 用户是否登录
      var self = this;
      if(self.isLogin) {
        dialog.tips({
          headerText: '修改密码',
          actionText: '修改密码',
          ok: function(reg) {
            if(reg.newRePassword != reg.newPassword) {
              return false;
            } else {
              if(!angular.isDefined(uid)) {
                uid = $cookieStore.get('id');
              }
              // 修改密码
              req.getdata('user/update_password', 'POST', function(data) {
                if(parseInt(data.status) === 0) {
                  dialog.tips({
                    bodyText:data.msg
                  });
                  self.isLogin = false;
                  $cookieStore.remove('type');
                  $cookieStore.remove('id');
                  req.redirect('/user/login');
                }else {
                  dialog.tips({
                    bodyText:data.msg
                  });
                }
              }, reg);
            }
            return false;
          }
        }, {
            templateUrl: 'password.html'
          }
        );
      }
    }
  };
  return {
    isLogin: user.isLogin,
    auth: user.getAuth,
    info: {
      id: $cookieStore.get('id'),
      type: $cookieStore.get('type')
    },
    logout: function() {
      var self = this;
      req.getdata('user/logout', 'GET', function(data) {
        $cookieStore.remove('type');
        $cookieStore.remove('id');
        req.redirect('/login');
      })
     },
    pwd: user.changePwd
  };
}]);
