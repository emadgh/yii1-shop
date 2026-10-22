/* Scoped PJAX: only same-page regions are replaced; regular navigation still works. */
(function(){
'use strict';
var main=document.getElementById('pjax-main');if(!main||!window.fetch||!window.history||!window.DOMParser)return;
var pending=new Map(),sequence=0,scrollTimer;
if('scrollRestoration' in history)history.scrollRestoration='manual';
function remember(){history.replaceState(Object.assign({},history.state,{yiiStorePjax:true,url:location.href,x:scrollX,y:scrollY}),'',location.href);}
remember();window.addEventListener('scroll',function(){clearTimeout(scrollTimer);scrollTimer=setTimeout(remember,150);},{passive:true});
function region(selector){if(!selector)return null;try{var node=document.querySelector(selector);return node&&main.contains(node)?node:null;}catch(e){return null;}}
function selectorFor(source,url){var next=new URL(url,location.href);if(next.pathname!==location.pathname)return null;var explicit=source&&source.getAttribute('data-pjax-target');if(explicit&&region(explicit))return explicit;var node=source&&source.closest('[data-pjax-region]');return node&&node.id?'#'+node.id:null;}
function focusState(target){var active=document.activeElement;if(!target.contains(active))return null;return {id:active.id,name:active.name,start:active.selectionStart,end:active.selectionEnd};}
function restoreFocus(target,saved){if(!saved)return;var node=saved.id?document.getElementById(saved.id):null;if(!node&&saved.name)node=Array.from(target.querySelectorAll('[name]')).find(function(n){return n.name===saved.name;});if(node&&target.contains(node)&&node.type!=='hidden'){node.focus({preventScroll:true});try{if(saved.start!==null)node.setSelectionRange(saved.start,saved.end);}catch(e){}}}
function scrollState(target){return [target].concat(Array.from(target.querySelectorAll('[data-scroll-memory],.table-wrap'))).map(function(n,i){return {id:n.id,index:i,x:n.scrollLeft,y:n.scrollTop};});}
function restoreInner(target,saved){var nodes=[target].concat(Array.from(target.querySelectorAll('[data-scroll-memory],.table-wrap')));saved.forEach(function(s){var node=s.id?document.getElementById(s.id):nodes[s.index];if(node&&(node===target||target.contains(node))){node.scrollLeft=s.x;node.scrollTop=s.y;}});}
async function navigate(url,push,options){
 options=options||{};var selector=options.selector||null,target=region(selector)||main;if(target===main)selector=null;
 var next=new URL(url,location.href),scope=target.dataset.pjaxScope;
 if(selector&&scope&&!options.body&&next.pathname===location.pathname){var merged=new URL(location.href),keys=new Set(Array.from(merged.searchParams.keys()).concat(Array.from(next.searchParams.keys()))),explicit=options.queryKeys?options.queryKeys.split(','):null;keys.forEach(function(name){var owned=explicit?explicit.includes(name):(name.indexOf('Grid['+scope+'][')===0||name===scope+'_sort'||name===scope+'_page');if(owned){merged.searchParams.delete(name);next.searchParams.getAll(name).forEach(function(v){merged.searchParams.append(name,v);});}});next=merged;}
 if(document.body.dataset.themePreview==='1'&&!/^\/admin/i.test(next.pathname))next.searchParams.set('_theme',document.body.dataset.storeTheme);url=next.href;
 // Overlapping replacements cannot race; unrelated grids may load independently.
 pending.forEach(function(job,key){if(job.target===target||job.target.contains(target)||target.contains(job.target)){job.controller.abort();pending.delete(key);}});
 var controller=new AbortController(),key=++sequence,savedFocus=focusState(target),busyTarget=region(options.busy)||target;
 var release=window.ShopUI?ShopUI.busy(busyTarget,options.body?'در حال ذخیره…':'در حال بارگذاری…'):function(){};pending.set(key,{controller:controller,target:target});
 if(push)remember();if(window.ShopUI)ShopUI.progress(true);
 var beforeUrl=location.href,preserve=!!selector||next.pathname===location.pathname||options.preserve;
 try{
  var headers={'X-Requested-With':'XMLHttpRequest','X-PJAX':'true','Accept':'text/html'};if(options.body)headers['X-PJAX-Form']='true';
  var response=await fetch(url,{method:options.body?'POST':'GET',body:options.body||undefined,credentials:'same-origin',signal:controller.signal,headers:headers});
  if(!response.ok)throw new Error('HTTP '+response.status);if(new URL(response.url).origin!==location.origin)throw new Error('External redirect');
  var html=await response.text();if(!pending.has(key)||!target.isConnected)return false;
  var dom=new DOMParser().parseFromString(html,'text/html'),wrapper=dom.querySelector('[data-pjax-title]');if(!wrapper)throw new Error('Missing PJAX response');
  if(wrapper.dataset.storeTheme&&wrapper.dataset.storeTheme!==document.body.dataset.storeTheme){location.assign(response.url);return false;}
  var content=selector?dom.querySelector(selector):wrapper;if(!content)throw new Error('Missing PJAX region');
  var x=scrollX,y=scrollY,inner=scrollState(target),holdHeight=!!selector&&getComputedStyle(target).position!=='sticky';
  if(holdHeight)target.style.minHeight=target.getBoundingClientRect().height+'px';
  target.innerHTML=content.innerHTML;
  if(selector&&window.ShopUI)wrapper.querySelectorAll('.flash').forEach(function(flash){ShopUI.toast(flash.textContent,flash.classList.contains('flash-error')?'error':'success');});
  // Refresh revision/data attributes while keeping the target element and its layout stable.
  if(selector)Array.from(content.attributes).forEach(function(a){if(a.name.indexOf('data-')===0)target.setAttribute(a.name,a.value);});
  document.title=wrapper.dataset.pjaxTitle||document.title;var description=document.querySelector('meta[name=description]');if(description)description.content=wrapper.dataset.pjaxDescription||'';
  [['og:title',document.title],['og:description',wrapper.dataset.pjaxDescription||'']].forEach(function(pair){var meta=document.querySelector('meta[property="'+pair[0]+'"]');if(meta)meta.content=pair[1];});
  var canonical=document.querySelector('link[rel=canonical]');if(canonical){var canonicalUrl=new URL(canonical.href);canonicalUrl.pathname=new URL(response.url).pathname;canonicalUrl.search='';canonical.href=canonicalUrl.href;var ogUrl=document.querySelector('meta[property="og:url"]');if(ogUrl)ogUrl.content=canonical.href;var ogImage=document.querySelector('meta[property="og:image"]');if(ogImage)ogImage.content=wrapper.dataset.pjaxImage?new URL(wrapper.dataset.pjaxImage,canonical.href).href:'';}
  var crumb=document.querySelector('.admin-breadcrumb');if(crumb)crumb.textContent='مدیریت / '+document.title;
  // Merge independent grid query changes if another region completed during this request.
  var finalUrl=new URL(response.url),requested=new URL(url),original=new URL(beforeUrl),current=new URL(location.href);
  if(selector&&current.pathname===original.pathname&&current.href!==original.href&&!options.body){var changed=new Set(Array.from(original.searchParams.keys()).concat(Array.from(requested.searchParams.keys())));changed.forEach(function(name){if(original.searchParams.get(name)!==requested.searchParams.get(name)){current.searchParams.delete(name);requested.searchParams.getAll(name).forEach(function(v){current.searchParams.append(name,v);});}});finalUrl=current;}
  if(push)history.pushState({yiiStorePjax:true,url:finalUrl.href,x:preserve?x:0,y:preserve?y:0},'',finalUrl.href);
  release();release=function(){};pending.delete(key);
  document.dispatchEvent(new CustomEvent('yii-store:pjax-loaded',{detail:{target:target,partial:!!selector}}));
  if(!options.resetInner)restoreInner(target,inner);else target.scrollTop=0;
  // A shorter last page must not make the browser clamp the current scroll position.
  if(holdHeight){target.style.minHeight='';var missing=y+innerHeight-document.documentElement.scrollHeight;if(missing>0)target.style.minHeight=Math.ceil(target.getBoundingClientRect().height+missing+2)+'px';}
  var validation=target.querySelector('.errorSummary');if(validation){var scrollBox=validation.closest('[data-scroll-memory]');if(scrollBox)scrollBox.scrollTop=0;validation.setAttribute('role','alert');validation.setAttribute('tabindex','-1');}
  if(options.restore){x=options.restore.x||0;y=options.restore.y||0;}else if(!preserve){x=0;y=0;}
  window.scrollTo({left:x,top:y,behavior:'instant'});
  if(validation)validation.focus({preventScroll:true});else if(preserve)restoreFocus(target,savedFocus);else main.focus({preventScroll:true});
  return true;
 }catch(error){
  if(error.name==='AbortError')return false;
  if(selector||options.body){if(window.ShopUI)ShopUI.toast('درخواست انجام نشد؛ اتصال را بررسی و دوباره تلاش کنید.','error');return false;}
  location.assign(url);return false;
 }finally{release();pending.delete(key);if(window.ShopUI)ShopUI.progress(false);}
}
window.ShopPjax={reload:function(selector){return navigate(location.href,false,{selector:selector,preserve:true});},navigate:function(url,options){return navigate(url,true,options||{});}};
document.addEventListener('click',function(event){
 var gallery=event.target.closest('[data-gallery-src]');if(gallery){var image=document.getElementById('main-product-image');if(image){image.src=gallery.dataset.gallerySrc;image.removeAttribute('srcset');}return;}
 var a=event.target.closest('a[href]');if(!a||event.defaultPrevented||event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
 if(a.hasAttribute('download')||a.hasAttribute('data-no-pjax')||a.target&&a.target!=='_self')return;if(!(a.closest('#pjax-main')||a.dataset.pjax==='1'))return;
 var url=new URL(a.href,location.href);if(url.origin!==location.origin||!/^https?:$/.test(url.protocol)||url.pathname===location.pathname&&url.search===location.search&&url.hash)return;
 if(/\/(?:uploads|assets)\//.test(url.pathname))return;event.preventDefault();navigate(url.href,true,{selector:selectorFor(a,url.href),queryKeys:a.dataset.pjaxKeys,resetInner:a.dataset.pjaxResetScroll==='1'});
});
document.addEventListener('submit',function(event){
 var form=event.target;if(event.defaultPrevented||form.dataset.pjaxForm!=='1')return;var method=(event.submitter&&event.submitter.getAttribute('formmethod')||form.method).toUpperCase();if(!['GET','POST'].includes(method))return;
 event.preventDefault();var url=new URL(event.submitter&&event.submitter.getAttribute('formaction')||form.action,location.href),data=new FormData(form);if(event.submitter&&event.submitter.name)data.append(event.submitter.name,event.submitter.value);
 if(method==='GET')data.forEach(function(value,key){url.searchParams.set(key,value);});
 navigate(url.href,true,{selector:selectorFor(form,url.href),queryKeys:form.dataset.pjaxKeys,busy:form.dataset.pjaxBusy,body:method==='POST'?data:null,preserve:method==='POST'});
});
window.addEventListener('popstate',function(event){clearTimeout(scrollTimer);navigate(location.href,false,{preserve:true,restore:event.state||{}});});
})();
