(function(){
'use strict';
var base=document.body.dataset.base||'';
var csrf=document.querySelector('meta[name="csrf-token"]');
var csrfName=document.querySelector('meta[name="csrf-name"]');
var pending=0,progressTimer;
var busySections=new WeakMap(),nativeBusyReleases=[];
window.ShopUI={
 progress:function(on){pending=Math.max(0,pending+(on?1:-1));var el=document.getElementById('page-progress');if(el)el.hidden=pending===0;},
 toast:function(message,type){var el=document.createElement('div');el.className='toast toast-'+(type||'success');el.textContent=message;document.getElementById('toast-region').appendChild(el);setTimeout(function(){el.remove();},6000);},
 confirm:function(message){return messageBox(message,'confirm');},
 alert:function(message){return messageBox(message,'alert');},
 prompt:function(message,value){return messageBox(message,'prompt',value);}
};
ShopUI.busy=function(element,label){
 if(!element)return function(){};var state=busySections.get(element);
 if(!state){
  var overlay=document.createElement('div');overlay.className='section-loading-overlay';overlay.setAttribute('role','status');overlay.setAttribute('aria-live','polite');
  var badge=document.createElement('div');badge.className='section-loading-label';var spinner=document.createElement('span');spinner.className='section-loading-spinner';spinner.setAttribute('aria-hidden','true');badge.append(spinner,document.createTextNode(label||'در حال بارگذاری…'));overlay.append(badge);
  state={count:0,overlay:overlay,aria:element.getAttribute('aria-busy'),position:getComputedStyle(element).position==='static',children:Array.from(element.children).map(function(n){var value=n.inert;n.inert=true;return {node:n,inert:value};})};
  if(state.position)element.classList.add('section-busy-position');element.classList.add('section-is-busy');element.setAttribute('aria-busy','true');element.append(overlay);
  state.place=function(){var rect=element.getBoundingClientRect(),start=Math.max(0,-rect.top),end=Math.min(rect.height,innerHeight-rect.top);badge.style.top=Math.max(20,(start+Math.max(start,end))/2)+'px';};
  state.place();window.addEventListener('scroll',state.place,{passive:true});window.addEventListener('resize',state.place);busySections.set(element,state);
 }
 state.count++;var released=false;return function(){if(released)return;released=true;if(--state.count>0)return;state.overlay.remove();state.children.forEach(function(c){c.node.inert=c.inert;});element.classList.remove('section-is-busy');if(state.position)element.classList.remove('section-busy-position');if(state.aria===null)element.removeAttribute('aria-busy');else element.setAttribute('aria-busy',state.aria);window.removeEventListener('scroll',state.place);window.removeEventListener('resize',state.place);busySections.delete(element);};
};
var queue=Promise.resolve();
function messageBox(message,type,value){
 var run=function(){return new Promise(function(resolve){
 var d=document.getElementById('message-dialog'),input=d.querySelector('input'),cancel=d.querySelector('[data-message-cancel]');
 d.querySelector('#message-title').textContent=type==='confirm'?'تأیید عملیات':type==='prompt'?'واردکردن اطلاعات':'پیام';
 d.querySelector('#message-body').textContent=message;input.hidden=type!=='prompt';input.value=value||'';cancel.hidden=type==='alert';d.returnValue='cancel';
 d.addEventListener('close',function closed(){d.removeEventListener('close',closed);resolve(type==='prompt'?(d.returnValue==='ok'?input.value:null):d.returnValue==='ok');},{once:true});
 d.showModal();if(type==='prompt')input.focus();
 });};var result=queue.then(run);queue=result.then(function(){},function(){});return result;
}
function api(path,data,section){
 var opt={credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}};
 if(data){opt.method='POST';if(!(data instanceof FormData)){var form=new FormData();Object.keys(data).forEach(function(k){form.append(k,data[k]);});data=form;}data.append(csrfName.content,csrf.content);opt.body=data;}
 var release=section?ShopUI.busy(section,data?'در حال انجام…':'در حال بارگذاری…'):function(){};ShopUI.progress(true);
 return fetch(base+path,opt).then(async function(r){var j;try{j=await r.json();}catch(e){throw new Error('دریافت اطلاعات ناموفق بود؛ دوباره تلاش کنید.');}if(!r.ok||j.ok===false){var error=new Error(j.error||'دریافت اطلاعات ناموفق بود.');error.payload=j;throw error;}return j;}).finally(function(){release();ShopUI.progress(false);});
}
window.ShopAPI=api;
function init(){
 document.querySelectorAll('.money-input').forEach(function(el){formatMoney(el);});
 document.querySelectorAll('.tag-input:not([data-ready])').forEach(tags);
 document.querySelectorAll('.markdown-editor:not([data-ready])').forEach(editor);
 document.querySelectorAll('.sidebar>a').forEach(function(a){a.classList.toggle('side-active',new URL(a.href).pathname.toLowerCase().split('/')[1]===location.pathname.toLowerCase().split('/')[1]);});
 document.querySelectorAll('.main-nav a').forEach(function(a){a.classList.toggle('nav-active',new URL(a.href).pathname===location.pathname);});
 document.querySelectorAll('.flash:not([data-announced])').forEach(function(el){el.setAttribute('role','status');el.dataset.announced='1';});
 if(document.getElementById('library-overview'))loadOverview();
}
function digits(v){return v.replace(/[۰-۹]/g,function(c){return '۰۱۲۳۴۵۶۷۸۹'.indexOf(c);}).replace(/[٠-٩]/g,function(c){return '٠١٢٣٤٥٦٧٨٩'.indexOf(c);});}
function formatMoney(el){var v=digits(el.value).replace(/[,٬\s]/g,'');if(/^\d*$/.test(v))el.value=v.replace(/\B(?=(\d{3})+(?!\d))/g,',');el.inputMode='numeric';}
document.addEventListener('input',function(e){if(e.target.matches('.money-input'))formatMoney(e.target);});
function tags(input){
 input.dataset.ready='1';var wrap=document.createElement('div');wrap.className='tag-field';var entry=document.createElement('input');entry.type='text';entry.placeholder='تگ را بنویسید و Enter بزنید';entry.setAttribute('aria-label','تگ جدید');
 input.type='hidden';input.parentNode.insertBefore(wrap,input);var values=input.value.split(/[,،\n]/).map(function(x){return x.trim();}).filter(Boolean);
 var list=document.createElement('div');list.className='tag-chips';wrap.append(list,entry);
 function render(){list.replaceChildren();values.forEach(function(v,i){var chip=document.createElement('span');chip.className='tag-chip';chip.append(document.createTextNode(v));var b=document.createElement('button');b.type='button';b.textContent='×';b.setAttribute('aria-label','حذف '+v);b.onclick=function(){values.splice(i,1);render();};chip.append(b);list.append(chip);});input.value=values.join(', ');}
 function add(){entry.value.split(/[,،]/).forEach(function(v){v=v.trim();if(v && values.indexOf(v)<0 && values.length<30)values.push(v);});entry.value='';render();}
 entry.addEventListener('keydown',function(e){if(['Enter',',','،'].includes(e.key)){e.preventDefault();add();}else if(e.key==='Backspace'&&!entry.value){values.pop();render();}});
 entry.addEventListener('blur',add);entry.addEventListener('paste',function(){setTimeout(function(){if(/[,،]/.test(entry.value))add();},0);});render();
}
function editor(textarea){
 textarea.dataset.ready='1';var box=document.createElement('div');box.className='markdown-box';var tools=document.createElement('div');tools.className='editor-toolbar';textarea.parentNode.insertBefore(box,textarea);box.append(tools,textarea);
 var preview=document.createElement('div');preview.className='markdown-preview rich-content';preview.hidden=true;box.append(preview);
 function insert(before,after){var start=textarea.selectionStart,end=textarea.selectionEnd,selected=textarea.value.slice(start,end);textarea.setRangeText(before+(selected||'متن')+(after||''),start,end,'select');textarea.focus();}
 [['B','متن پررنگ','**','**'],['I','متن مورب','*','*'],['H2','عنوان','\n## ',''],['≡','فهرست','\n- ',''],['❞','نقل قول','\n> ','']].forEach(function(x){var b=document.createElement('button');b.type='button';b.textContent=x[0];b.title=x[1];b.setAttribute('aria-label',x[1]);b.onclick=function(){insert(x[2],x[3]);};tools.append(b);});
 var link=document.createElement('button');link.type='button';link.textContent='پیوند';link.onclick=async function(){var url=await ShopUI.prompt('آدرس پیوند را وارد کنید','https://');if(url&&/^https?:\/\//i.test(url))insert('[',']('+url+')');};tools.append(link);
 var media=document.createElement('button');media.type='button';media.textContent='رسانه';media.onclick=function(){openMedia({multiple:false,accept:'image/*,application/pdf,text/plain',ids:[],onApply:function(rows){if(rows[0])insert(rows[0].mime.indexOf('image/')===0?'![':'[',']('+rows[0].url+')');}});};tools.append(media);
 var toggle=document.createElement('button');toggle.type='button';toggle.textContent='پیش‌نمایش';toggle.className='preview-toggle';toggle.onclick=async function(){if(!preview.hidden){preview.hidden=true;textarea.hidden=false;toggle.textContent='پیش‌نمایش';return;}try{var r=await api('/adminMedia/preview',{text:textarea.value},box);preview.innerHTML=r.html;preview.hidden=false;textarea.hidden=true;toggle.textContent='ویرایش';}catch(e){ShopUI.toast(e.message,'error');}};tools.append(toggle);
 var hint=document.createElement('small');hint.className='editor-hint';hint.textContent='Markdown · متن پررنگ، عنوان، فهرست، پیوند و تصویر؛ همراه با پیش‌نمایش';box.append(hint);
}
document.addEventListener('click',function(e){
 var add=e.target.closest('[data-add-pair]');
 if(add){var box=add.closest('.pair-editor'),rows=box.querySelector('.pair-rows'),index=Date.now().toString()+Math.floor(Math.random()*10000),row=document.createElement('div');row.className='pair-row';['key','value'].forEach(function(k){var input=document.createElement('input');input.name=box.dataset.name+'['+index+']['+k+']';input.maxLength=k==='key'?100:2000;input.placeholder=k==='key'?'نام مشخصه':'مقدار';input.setAttribute('aria-label',input.placeholder);row.append(input);});var b=document.createElement('button');b.type='button';b.className='icon-button';b.dataset.removePair='';b.setAttribute('aria-label','حذف ردیف');b.textContent='×';row.append(b);rows.append(row);row.querySelector('input').focus();}
 var remove=e.target.closest('[data-remove-pair]');if(remove)remove.closest('.pair-row').remove();
 var open=e.target.closest('[data-open-media]');if(open){var picker=open.closest('.media-picker');openMedia({multiple:picker.dataset.multiple==='1',accept:picker.dataset.accept,ids:picker.querySelector('.media-value').value.split(',').filter(Boolean),onApply:function(rows){picker.querySelector('.media-value').value=rows.map(function(r){return r.id;}).join(',');renderSelection(picker.querySelector('.media-selection'),rows);picker.querySelector('.media-value').dispatchEvent(new Event('change',{bubbles:true}));}});}
 var clear=e.target.closest('[data-clear-media]');if(clear){var picker=clear.closest('.media-picker');picker.querySelector('.media-value').value='';picker.querySelector('.media-selection').replaceChildren();picker.querySelector('.media-value').dispatchEvent(new Event('change',{bubbles:true}));}
 if(e.target.closest('[data-library-open]'))openMedia({multiple:true,accept:'*',ids:[],library:true,onApply:function(){}});
 if(e.target.closest('[data-close-media]'))document.getElementById('media-dialog').close();
 if(e.target.closest('[data-apply-coupon]')){var input=document.querySelector('[name=coupon]');api('/cart/coupon',{code:input.value},input.closest('.panel')||input.parentElement).then(function(r){document.getElementById('coupon-feedback').textContent='تخفیف: '+r.discount+' · قابل پرداخت: '+r.total;ShopUI.toast('کد تخفیف معتبر است؛ هنگام ثبت نهایی دوباره بررسی می‌شود.');}).catch(function(err){document.getElementById('coupon-feedback').textContent=err.message;ShopUI.toast(err.message,'error');});}
});
document.addEventListener('change',function(e){if(e.target.matches('[data-discount-kind]')){var input=e.target.closest('form').querySelector('[data-discount-amount]');input.classList.toggle('money-input',e.target.value==='fixed');formatMoney(input);if(e.target.value!=='fixed')input.value=input.value.replace(/,/g,'');}});
document.addEventListener('submit',function(e){
 var form=e.target;
 if(form.hasAttribute('data-confirm')&&!form.dataset.confirmed){e.preventDefault();e.stopImmediatePropagation();var submitter=e.submitter;ShopUI.confirm(form.dataset.confirm).then(function(ok){if(ok){form.dataset.confirmed='1';form.requestSubmit(submitter);delete form.dataset.confirmed;}});return;}
 if(!form.hasAttribute('data-cart-form')&&!e.defaultPrevented&&form.method.toUpperCase()==='POST'&&!(form.dataset.pjaxForm==='1'&&window.ShopPjax)){nativeBusyReleases.push(ShopUI.busy(form.closest('[data-pjax-region],.panel')||form,'در حال ثبت…'));ShopUI.progress(true);}
},true);
window.addEventListener('beforeunload',function(){ShopUI.progress(true);});
window.addEventListener('pageshow',function(){nativeBusyReleases.splice(0).forEach(function(release){release();});pending=0;ShopUI.progress(false);});
document.addEventListener('yii-store:pjax-loaded',init);
var mediaState,mediaRequest=0,mediaSearchTimer;
function accepts(row,accept){return !accept||accept==='*'||accept.split(',').some(function(x){x=x.trim();return x==='image/*'?row.mime.indexOf('image/')===0:row.mime===x;});}
function renderSelection(target,rows){target.replaceChildren();rows.forEach(function(r){var card=document.createElement('div');card.className='selected-media';card.dataset.id=r.id;if(r.thumb){var img=document.createElement('img');img.src=r.thumb;img.alt=r.original_name;card.append(img);}else card.textContent=r.mime==='application/pdf'?'PDF':'TXT';var name=document.createElement('small');name.textContent=r.original_name;card.append(name);target.append(card);});}
function openMedia(options){
 mediaState={multiple:!!options.multiple,accept:options.accept,ids:options.ids.map(String),selected:new Map(),onApply:options.onApply,library:options.library,offset:0,q:''};
 var dialog=document.getElementById('media-dialog');dialog.querySelector('h2').textContent=options.library?'مدیریت رسانه‌ها':'انتخاب رسانه';document.getElementById('media-apply').textContent=options.library?'بستن کتابخانه':'استفاده از انتخاب‌ها';document.getElementById('media-search').value='';document.getElementById('media-upload').accept=options.accept==='*'?'image/jpeg,image/png,image/webp,application/pdf,text/plain':options.accept;document.getElementById('media-feedback').textContent='';dialog.showModal();loadMedia(true);
}
window.MediaLibrary={open:openMedia};
function updateCount(){document.getElementById('media-count').textContent=mediaState.ids.length+' فایل انتخاب شده · '+(mediaState.multiple?'چند انتخاب':'یک انتخاب');}
function mediaCard(r,overview){
 var card=document.createElement('div');card.className='media-tile';var b=document.createElement('button');b.type='button';b.className='media-tile-select';b.dataset.id=r.id;
 if(r.thumb){var img=document.createElement('img');img.src=r.thumb;img.alt=r.original_name;img.loading='eager';b.append(img);}else{var icon=document.createElement('span');icon.className='file-icon';icon.textContent=r.mime==='application/pdf'?'PDF':'TXT';b.append(icon);}
 var name=document.createElement('strong');name.textContent=r.original_name;var detail=document.createElement('small');detail.textContent=r.width?r.width+' × '+r.height+' · WebP':Math.ceil(r.size/1024)+' KB';b.append(name,detail);
 b.onclick=function(){if(overview){openMedia({multiple:true,accept:'*',ids:[String(r.id)],library:true,onApply:function(){}});return;}if(!accepts(r,mediaState.accept)){ShopUI.toast('این فرمت در این فیلد مجاز نیست.','error');return;}
 var key=String(r.id),pos=mediaState.ids.indexOf(key);if(pos>=0)mediaState.ids.splice(pos,1);else{if(!mediaState.multiple)mediaState.ids=[];if(mediaState.ids.length>=20){ShopUI.toast('حداکثر ۲۰ رسانه انتخاب کنید.','error');return;}mediaState.ids.push(key);}mediaState.selected.set(key,r);document.querySelectorAll('#media-grid .media-tile-select').forEach(function(x){x.setAttribute('aria-pressed',String(mediaState.ids.includes(x.dataset.id)));});updateCount();};
 if(mediaState&&!overview)b.setAttribute('aria-pressed',String(mediaState.ids.includes(String(r.id))));card.append(b);
 if(!overview&&mediaState.library){var del=document.createElement('button');del.type='button';del.className='media-delete';del.textContent='حذف فایل';del.onclick=async function(){if(await ShopUI.confirm('فایل از کتابخانه حذف شود؟')){try{await api('/adminMedia/delete?id='+r.id,{});loadMedia(true);loadOverview();}catch(e){ShopUI.toast(e.message,'error');}}};card.append(del);}
 return card;
}
async function loadMedia(reset){
 var state=mediaState,request=++mediaRequest,grid=document.getElementById('media-grid');if(reset){state.offset=0;grid.replaceChildren();grid.setAttribute('aria-busy','true');}
 try{var response=await api('/adminMedia/list?q='+encodeURIComponent(state.q)+'&offset='+state.offset,null,document.querySelector('.media-library-scroll'));if(request!==mediaRequest||state!==mediaState)return;
 response.items.forEach(function(r){state.selected.set(String(r.id),r);if(accepts(r,state.accept)||state.library)grid.append(mediaCard(r,false));});state.offset+=response.items.length;document.getElementById('media-more').hidden=!response.hasMore;
 if(!grid.children.length){var empty=document.createElement('p');empty.className='media-empty';empty.textContent='رسانه‌ای پیدا نشد. فایل جدید آپلود کنید.';grid.append(empty);}updateCount();
 }catch(e){ShopUI.toast(e.message,'error');}finally{grid.removeAttribute('aria-busy');}
}
async function uploadFiles(files){
 if(!mediaState)return;var list=Array.from(files),feedback=document.getElementById('media-feedback'),state=mediaState;var count=0;
 for(var file of list){if(file.size>10*1024*1024){ShopUI.toast(file.name+': بیش از ۱۰ مگابایت','error');continue;}feedback.textContent='آپلود '+file.name+' ('+(++count)+' از '+list.length+')';
 try{var form=new FormData();form.append('file',file);var result=await api('/adminMedia/upload',form,document.getElementById('media-drop'));var row=result.item;if(state===mediaState&&accepts(row,state.accept)){state.selected.set(String(row.id),row);if(state.multiple&&state.ids.length<20)state.ids.push(String(row.id));else if(!state.multiple)state.ids=[String(row.id)];}}catch(e){ShopUI.toast(file.name+': '+e.message,'error');}}
 feedback.textContent='آپلودها پردازش شدند.';if(state===mediaState)loadMedia(true);loadOverview();
}
function loadOverview(){var target=document.getElementById('library-overview');if(!target)return;api('/adminMedia/list').then(function(r){target.replaceChildren();r.items.forEach(function(row){target.append(mediaCard(row,true));});}).catch(function(e){ShopUI.toast(e.message,'error');});}
var dialog=document.getElementById('media-dialog');
if(dialog){
 dialog.addEventListener('close',function(){mediaRequest++;mediaState=null;document.getElementById('media-grid').replaceChildren();});
 document.getElementById('media-search').oninput=function(e){clearTimeout(mediaSearchTimer);mediaSearchTimer=setTimeout(function(){mediaState.q=e.target.value;loadMedia(true);},250);};
 document.getElementById('media-more').onclick=function(){loadMedia(false);};
 document.getElementById('media-upload').onchange=function(e){uploadFiles(e.target.files);e.target.value='';};
 var drop=document.getElementById('media-drop');['dragenter','dragover'].forEach(function(name){dialog.addEventListener(name,function(e){e.preventDefault();drop.classList.add('drag-over');});});dialog.addEventListener('dragleave',function(e){if(!dialog.contains(e.relatedTarget))drop.classList.remove('drag-over');});
 dialog.addEventListener('drop',function(e){e.preventDefault();drop.classList.remove('drag-over');uploadFiles(e.dataTransfer.files);});
 document.getElementById('media-apply').onclick=async function(){var state=mediaState,rows=state.ids.map(function(id){return state.selected.get(id);}).filter(Boolean);
 if(rows.length!==state.ids.length){try{for(var offset=0;rows.length!==state.ids.length;offset+=48){var res=await api('/adminMedia/list?offset='+offset);res.items.forEach(function(r){state.selected.set(String(r.id),r);});rows=state.ids.map(function(id){return state.selected.get(id);}).filter(Boolean);if(!res.hasMore)break;}}catch(e){ShopUI.toast(e.message,'error');return;}}
 if(rows.length!==state.ids.length){ShopUI.toast('بعضی فایل‌ها پیدا نشدند؛ انتخاب را بررسی کنید.','error');return;}
 state.onApply(rows);dialog.close();};
}
init();pending=0;ShopUI.progress(false);
})();
