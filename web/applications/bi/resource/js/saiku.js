function reinitIframe(){
  var iframe = document.getElementById("saiku_iframe");
  try{
    iframe.height =  iframe.contentWindow.document.documentElement.scrollHeight;
  }catch (ex){}
}

window.setInterval("reinitIframe()", 200);

