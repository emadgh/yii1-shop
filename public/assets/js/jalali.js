(function(){
'use strict';
var base=(document.body.dataset.base||'')+'/assets/',loading;
function stylesheet(path){
 return new Promise(function(resolve,reject){var link=document.createElement('link');link.rel='stylesheet';link.href=base+path;link.onload=resolve;link.onerror=function(){link.remove();reject(new Error('Calendar stylesheet unavailable'));};document.head.append(link);});
}
function ensurePicker(){
 if(loading)return loading;
 var script=document.createElement('script');script.src=base+'vendor/jalalidatepicker/jalalidatepicker.min.js';
 var library=new Promise(function(resolve,reject){script.onload=resolve;script.onerror=function(){reject(new Error('Calendar script unavailable'));};document.head.append(script);});
 loading=Promise.all([library,stylesheet('vendor/jalalidatepicker/jalalidatepicker.min.css'),stylesheet('css/jalali.css')]).then(function(){
  window.jalaliDatepicker.startWatch({time:true,hasSecond:false,zIndex:5000});
 }).catch(function(error){script.remove();loading=null;throw error;});
 return loading;
}
function init(){
 if(!document.querySelector('input[data-jdp]'))return;
 ensurePicker().then(function(){var active=document.activeElement;if(active&&active.matches('input[data-jdp]'))window.jalaliDatepicker.show(active);}).catch(function(){if(window.ShopUI)ShopUI.toast('انتخابگر تاریخ بارگذاری نشد؛ تاریخ را دستی وارد کنید.','error');});
}
document.addEventListener('yii-store:pjax-loaded',function(){if(window.jalaliDatepicker)window.jalaliDatepicker.hide();init();});
document.addEventListener('focusin',function(e){if(e.target.matches('input[data-jdp]')&&!loading)init();});
init();
})();
