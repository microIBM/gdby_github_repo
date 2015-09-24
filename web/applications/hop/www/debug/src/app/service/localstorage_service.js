'use strict';
/**
 * localStorage service封装
 */
angular
  .module('hop')
  .factory('daChuLocal', function() {
  var local = {
    get: function(name) {
      var res = localStorage.getItem(name);
      if(res) {
        return JSON.parse(res);
      } else {
        return null;
      }
    },
    set: function(name, jsonObj) {
      localStorage.setItem(name, JSON.stringify(jsonObj));
    },
    remove: function(name) {
      localStorage.removeItem(name);
    }
  };
  return local;
});
