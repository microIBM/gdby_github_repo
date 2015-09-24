'use strict';
/**
 * localStorage service封装
 */
angular.module('hop')
.factory('daChuLocal', function() {
  var local = {
    get: function(name) {
      return JSON.parse(localStorage.getItem(name));
    },
    set: function(name, jsonObj) {
      localStorage.setItem(name, JSON.stringify(jsonObj));
    }
  };
  return local;
});
