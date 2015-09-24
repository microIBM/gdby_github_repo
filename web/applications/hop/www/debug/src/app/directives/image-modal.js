'use strict';

angular
.module('dachu.widget.imagebox', [])
  .directive('imageModal',function($compile) {
    return {
      require: '^imageBox',
      restrict: 'E',
      transclude: true,
      scope: {url:"@",title:"="},
      link: function (scope, element, attrs, pctrl) {
          var listArr=[];
          scope._count_=0;

          scope.$watch("url", function (newValue,oldValue) {
              if(newValue=="" && typeof scope.url == "string")
                  return;
              else if (newValue.length==0  && typeof scope.url == "array")
                  return;

              scope.showIndex=attrs.show;
              scope._showIndex_= parseInt(attrs.show)+1;
              var hdr = element.find(".image-placeholder");
              hdr.children().remove();

              if(typeof scope.url == "array"){
                  listArr=scope.url;
              }else if(typeof scope.url == "string"){
                   if(scope.url.indexOf(",")>-1){
                     var splitArr=scope.url.split(',');
                     listArr=splitArr;
                   }else{
                       listArr.push(scope.url);
                   }

                   scope._count_=listArr.length;
              }       
              console.log("length:"+listArr.length);
              angular.forEach(listArr,function(itemUrl,index){
                console.log("itemUrl:"+itemUrl);
                  if(itemUrl!=""){
                     if(scope.showIndex==index){
                            hdr.append("<img ng-show='showIndex=="+(index)+"' style='width:400px;height:300px;' src="+itemUrl+"  class='show'>");
                     }else{
                            hdr.append("<img ng-show='showIndex=="+(index)+"' style='width:400px;height:300px;' src="+itemUrl+">");
                     }
                     $compile(element.contents())(scope);
                  }
              })
          });

          scope.close=function(){
                element.parent(".tab-content-image").hide();
          }

          scope.next=function(){
                var nextInt=parseInt(attrs.show)==(listArr.length-1)?parseInt(attrs.show):parseInt(attrs.show)+1;
                scope.showIndex= nextInt;
                element.attr("show", nextInt);
                scope._showIndex_=nextInt+1;
          }

          scope.prev=function(){
                var prevInt=parseInt(attrs.show)==0?0:parseInt(attrs.show)-1;
                scope.showIndex= prevInt;
                element.attr("show",prevInt);
                scope._showIndex_=prevInt+1;
          }
      },
      template:
          '<div style="position:fixed;z-index:1038;left:0;top:0;">' +
                '<div style="position:fixed;padding:30px;">'+
                      '<div style="position:fixed;margin:15% 0 0 35%;width:420px;height:368px;padding:10px;background:#ddd;left:0;top:0;bottom:0;right:0;">'+   
                           '<div class="image-placeholder"></div>' +
                           '<div style="width:400px;height:48px;" class="btn-button">'+
                               '<ul style="list-style-type:none;">'+
                                 '<li style="float:left;padding:10px 5px;">{{_count_}}/{{ _showIndex_ }}</li>'+
                                 '<li style="padding:10px 23px;margin:0px 12px;float:left;background:#296ad4;color:white;" ng-click="prev()">上一张</li>'+
                                 '<li style="padding:10px 23px;margin:0px 12px;float:left;background:#296ad4;color:white;" ng-click="next()">下一张</li>'+
                                 '<li style="padding:10px 23px;margin:0px 12px;float:left;background:#296ad4;color:white;" ng-click="close()">关闭</li>'+
                                '</ul>'+
                           '</div>'+
                      '</div>' +
                '</div>'+
          '</div>',
      replace: true
    };
}).directive('imageBox',function() {
    return {
          restrict: 'E',
          transclude: true,
          scope: { url: '@' },
          link: function(scope, element, attrs) {
               scope.$watch("url", function () {
                   if(scope.url!=""){
                     var hdr = element.find(".image-Box");
                     hdr.html("<img alt='点击放大' src="+scope.url+" style='width:120px;height:120px;'/>");
                   }
               });

               scope.open=function(){
                     element.find(".tab-content-image").show();
               }
          },
          controller:[ "$scope", function($scope) {
             
          }],
          template:
              '<div>' +
               '<div class="image-Box" ng-click="open()"></div>' +
                 '<div style="display:none;" class="tab-content-image" ng-transclude></div>' +
              '</div>',
          replace: true
    };
})
