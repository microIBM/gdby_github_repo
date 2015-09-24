'use strict';
angular.module('dachuwang')
.factory('posService', ['daChuLocal', function(daChuLocal) {
    var localInfo = daChuLocal.get('currentLocation'),
    localId = localInfo ? localInfo.id : 804,
    localName = localInfo ? localInfo.name : '北京';
    var obj = {
      id : localId,
      name : localName
    };
    return {
      setInfo : function(id, name) {
        obj.id = id;
        obj.name = name;
        daChuLocal.set('currentLocation', obj);
      },
      info : function() {
        return obj;
      }
    };
}]);
