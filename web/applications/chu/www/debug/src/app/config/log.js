angular.module('dachuwang')
.config(logConfig);

function logConfig($provide ){
  $provide.decorator('$exceptionHandler',function($delegate){
    return function(exception , cause){

      $delegate(exception, cause);
      var url = window.location.href;
      var logData = {};
      var httpUrl;
      logData.msg = exception.message;
      logData.time = new Date().getTime();
      logData.stack = exception.stack;
      logData.browser = checkUa().browserName;
      logData.url = url;
      logData = formatParams(logData)

      // 线上跟测试区分
      if(url.match(/test|127.0.0.1|localhost/g)){
        httpUrl = 'http://weblogs.test5.dachuwang.com';
      }else{
        httpUrl = 'http://api.weblogs.test.dachuwang.com'
      }

      $http(httpUrl + '/cron/write_log' , 'post' , logData , function(data){
        data = JSON.parse(data);
      }, function(data){
      })
    }
  })
}

// config 是在模块加载前进行， 所以封装一个原生的ajax
function $http(url , method , data , successCb , errorCb){

  var xhr = null;
  if(window.XMLHttpRequest){
    xhr = new XMLHttpRequest();
  }else{
    xhr = new ActiveObject('Microsoft.XMLHTTP');
  }

  var method = method.toUpperCase();
  var random = Math.random();

  if(method == 'GET'){
    if(data){
      xhr.open('GET' , url + '?' + data , true );
    }else{
      xhr.open('GET' , url + '?r=' + random , true);
    }
    xhr.send();
  }else if(method == 'POST'){
    xhr.open('POST' , url  , true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.send(data);
  }

  xhr.onreadystatechange = function(){
    if(xhr.readyState == 4 ){
      if(xhr.status == 200){
        successCb && successCb(xhr.responseText);
      }else{
        errorCb && errorCb(xhr.status) ;
      }
    }
  }
}

// 格式化参数
function formatParams(data) {
  var arr = [];
  for (var name in data) {
    arr.push(encodeURIComponent(name) + "=" + encodeURIComponent(data[name]));
  }
  return arr.join("&");
}

// 检查是手机还是电脑
function checkUa(){
  var browserName;
  var w = window.innerWidth;
  if(w < 768){
    browserName = checkMobile().browserName;
  }else{
    browserName = checkBrowser().browserName;
  }

  return {
    browserName : browserName
  }
}

// 获取浏览器版本
function checkBrowser(){
  var browserName ;
  var Sys = {};
  var ua = navigator.userAgent.toLowerCase();
  var s;
  (s = ua.match(/msie ([\d.]+)/)) ? Sys.ie = s[1] :
    (s = ua.match(/firefox\/([\d.]+)/)) ? Sys.firefox = s[1] :
    (s = ua.match(/chrome\/([\d.]+)/)) ? Sys.chrome = s[1] :
    (s = ua.match(/opera.([\d.]+)/)) ? Sys.opera = s[1] :
    (s = ua.match(/version\/([\d.]+).*safari/)) ? Sys.safari = s[1] : 0;
  if (Sys.ie) browserName = 'IE: ' + Sys.ie;
  if (Sys.firefox) browserName = 'Firefox: ' + Sys.firefox;
  if (Sys.chrome) browserName = 'Chrome: ' + Sys.chrome;
  if (Sys.opera) browserName = 'Opera: ' + Sys.opera;
  if (Sys.safari) browserName = 'Safari: ' + Sys.safari;

  if(!browserName){
    browserName = ua;
  }

  return {
    browserName : browserName
  }
}

// 获取手机型号
function checkMobile(){
  var browserName;
  if(/android/i.test(navigator.userAgent)){
    browserName = 'android';
  }
  if(/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
    browserName = 'ios';
  }
  if(/Linux/i.test(navigator.userAgent)){
    browserName = 'linux browser';
  }
  if(/MicroMessenger/i.test(navigator.userAgent)){
    browserName = 'MicroMessenger';
  }
  return {
    browserName : browserName
  }
}
