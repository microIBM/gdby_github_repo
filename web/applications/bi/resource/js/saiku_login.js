window.onload = function(){
  var submit = document.getElementById("saiku_login");
  submit.submit();
  var search = urlArgs();
  var args = setUrl(search);
  var url = 'http://saiku.dachuwang.com/api.html?#query/open/api/'+args;
  window.location.href = url; //刷新页面
}

//获取url中的参数,返回一个数组
function urlArgs() {
  var args = {};
  var query = window.location.search.substring(1);//获取url问号后面的内容，不包括“？”
  var pairs = query.split("&");
  var argsLen = pairs.length;
  for (var  i = 0; i < argsLen; i++){
    var pos = pairs[i].indexOf('=');
    if (pos == -1) continue;
    var name = pairs[i].substring(0, pos);
    var value = pairs[i].substring(pos+1);
    value = decodeURIComponent(value);
    args[name] = value;
  }
  return args;
}

//根据传如的数组，拼装url
function setUrl(search){
  var DACHU = "1";  //大厨
  var DAGUO = "2";  //大果

  var QUANGUO  = "0";   //全国
  var BEIJING  = "804"; //北京
  var SHANGHAI = "993"; //上海
  var TIANJIN  = "1206";//天津

  var site = 'chu'; //默认大厨
  var city = 'all';   //默认全国
  switch(search['site_id']){
  case DACHU:
    site = 'chu';
    break;
  case DAGUO:
    site ='guo';
    break;
  default:
    site = 'chu';
    break;
  }

  switch(search['city_id']){
  case QUANGUO:
    city = 'all';
    break;
  case BEIJING:
    city ='beijing';
    break;
  case SHANGHAI:
    city ='shanghai';
    break;
  case TIANJIN:
    city ='tianjin';
    break;
  default:
    city = 'all';
    break;
  }
  var city_site = city +'_'+site+'_cat.saiku';
  return city_site;
}
