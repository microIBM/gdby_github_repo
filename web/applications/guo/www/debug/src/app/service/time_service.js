'use strict';
angular
  .module('dachuwang')
  .factory('daChuTimer', function() {
    var timePicker = {
      getNow: function() {
        return parseInt((new Date().getTime()) / 1000);
      },
      cacheTime: 30
    };
    return {
      getNow: timePicker.getNow,
      setCacheTime: function(t) {
        timePicker.cacheTime = parseInt(t);
      },
      compare: function(q) {
        var now = this.getNow();
        if(parseInt(q) + timePicker.cacheTime > now) {
          return true;
        }
        return false;
      }
    };
  });
