(function(){
'use strict';
var queue=Promise.resolve(),pendingForms=new WeakSet();
function popup(){return document.getElementById('navbar-cart-popup');}
function setOpen(open,focus){var panel=popup(),toggle=document.querySelector('[data-cart-toggle]');if(!panel||!toggle)return;panel.hidden=!open;toggle.setAttribute('aria-expanded',String(open));if(open){place();if(focus)panel.querySelector('button,a,input')?.focus();}else if(focus)toggle.focus();}
function place(){var panel=popup(),toggle=document.querySelector('[data-cart-toggle]');if(!panel||panel.hidden)return;var rect=toggle.getBoundingClientRect();panel.style.top=Math.max(8,Math.min(innerHeight-100,rect.bottom+10))+'px';panel.style.left=Math.max(10,Math.min(innerWidth-panel.offsetWidth-10,rect.right-panel.offsetWidth))+'px';panel.style.maxHeight=Math.max(90,innerHeight-parseFloat(panel.style.top)-12)+'px';}
function apply(result,updatePage){document.querySelectorAll('.cart-number').forEach(function(el){el.textContent=result.count;});var mini=document.querySelector('[data-mini-cart-content]'),page=document.querySelector('[data-cart-page]');if(mini)mini.innerHTML=result.mini;if(page&&updatePage)page.innerHTML=result.page;}
function request(path,data,section,open){
 queue=queue.catch(function(){}).then(async function(){if(section&&!section.isConnected)section=document.querySelector(section.hasAttribute('data-cart-page')?'[data-cart-page]':'[data-mini-cart-content]');var release=ShopUI.busy(section,'در حال بروزرسانی سبد…');try{var result=await ShopAPI(path,data);apply(result,!!data);if(open)setOpen(true);if(data)ShopUI.toast(result.message||'سبد خرید به‌روز شد.');}catch(e){if(e.payload&&typeof e.payload.mini==='string')apply(e.payload,true);ShopUI.toast(e.message,'error');}finally{release();}});return queue;
}
document.addEventListener('submit',function(e){var form=e.target;if(!form.matches('[data-cart-form]')||e.defaultPrevented)return;e.preventDefault();if(pendingForms.has(form))return;pendingForms.add(form);var data=new FormData(form);if(e.submitter?.name==='remove')data.set('quantity','0');request(new URL(form.action).pathname,data,form.closest('[data-cart-page],[data-mini-cart-content]')||form,form.dataset.cartForm==='add').finally(function(){pendingForms.delete(form);});});
document.addEventListener('click',function(e){if(e.target.closest('[data-cart-toggle]')){var open=popup()?.hidden;setOpen(open);if(open)request('/cart/summary',null,document.querySelector('[data-mini-cart-content]'));return;}if(e.target.closest('[data-cart-close]')){setOpen(false,true);return;}if(!e.target.closest('.navbar-cart')||e.target.closest('.navbar-cart a'))setOpen(false);});
document.addEventListener('keydown',function(e){if(e.key==='Escape'&&popup()&&!popup().hidden){e.preventDefault();setOpen(false,true);}});
document.addEventListener('yii-store:pjax-loaded',function(){setOpen(false);if(popup())request('/cart/summary',null,null);});
window.addEventListener('resize',place);window.addEventListener('scroll',place,{passive:true});
window.addEventListener('pageshow',function(e){if(e.persisted&&popup())request('/cart/summary',null,null);});
})();
