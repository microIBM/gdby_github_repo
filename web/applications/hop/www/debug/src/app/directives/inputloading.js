angular
.module('dachu.widget.inputloading', [])
  .directive('dachuInputLoading',function($compile) {
      return {
            restrict: 'A',
            transclude: true,
            scope: { isShow: '=show',src:'@' },
            replace: false,
            link: function(scope, element, attrs) {
              scope.defaultLoadingSrc=scope.src||"";
              if(scope.defaultLoadingSrc=='')
                      scope.defaultLoadingSrc='/assets/images/input-loading.gif';
              
              var templateStr='<div ng-show="isShow>-1" style="width:34px;height:34px;right:0;top:0;position:absolute;">'
                            +'<img ng-show="isShow==1" style="width:24px;heigh:24px;left:0;right:0;margin:5px -10px;" src="{{defaultLoadingSrc}}">'
                            +'<span ng-show="isShow==0" style="width:24px;height:24px;margin:5px -10px;position:absolute;">✅</span>'
                             +'<span ng-show="isShow==2" style="width:24px;height:24px;margin:5px -10px;position:absolute;">❌</span>'
                            +'</div>';
              var str = $compile(templateStr)(scope);
              element.append(str);
            },
            template:
              '<div style="position:relative" ng-transclude></div>',
      };
  })