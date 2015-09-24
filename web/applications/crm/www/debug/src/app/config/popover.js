'use strict';

angular.module('dachuwang')
  .config(['$tooltipProvider', function($tooltipProvider) {
    $tooltipProvider.setTriggers({'click':'mouseleave'});
  }]);
