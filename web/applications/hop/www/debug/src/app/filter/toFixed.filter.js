'user strict';

angular.module("hop").filter("onFixed",function(){
   return function(input,param){
      var number=new Number(input);
     // number = number.toFixed(param);
      console.log(number.toFixed(param));
      return 0;
   }
})
