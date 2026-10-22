/* Progressive enhancement: no dependency on Yii2/jquery-pjax. Full navigation always works. */
(function () {
  'use strict';
  var target = document.getElementById('pjax-main');
  if (!target || !window.fetch || !window.history || !window.DOMParser) return;
  var counter = 0;
  var controller = null;
  var initialUrl = location.href;
  history.replaceState({yiiStorePjax:true,url:initialUrl}, '', location.href);

  function navigate(url, push) {
    if(document.body.dataset.themePreview==='1'){var previewUrl=new URL(url,location.href);if(!/^\/admin/i.test(previewUrl.pathname))previewUrl.searchParams.set('_theme',document.body.dataset.storeTheme);url=previewUrl.href;}
    if (controller) controller.abort();
    controller = new AbortController();
    var requestNumber = ++counter;
    target.classList.add('loading-pjax');
    if(window.ShopUI) window.ShopUI.progress(true);
    return fetch(url, {
      method:'GET', credentials:'same-origin', signal:controller.signal,
      headers:{'X-Requested-With':'XMLHttpRequest','X-PJAX':'true','Accept':'text/html'}
    }).then(function (response) {
      if (!response.ok) throw new Error('HTTP '+response.status);
      if (new URL(response.url).origin !== location.origin) throw new Error('External redirect');
      return response.text().then(function(text) { return {html:text,finalUrl:response.url}; });
    }).then(function (res) {
      if (counter !== requestNumber) return;
      var dom = new DOMParser().parseFromString(res.html, 'text/html');
      var wrapper = dom.querySelector('[data-pjax-title]');
      if (!wrapper) throw new Error('Missing PJAX content');
      if(wrapper.dataset.storeTheme && document.body.dataset.storeTheme && wrapper.dataset.storeTheme!==document.body.dataset.storeTheme){location.assign(res.finalUrl);return;}
      target.innerHTML = wrapper.innerHTML;
      document.title = wrapper.getAttribute('data-pjax-title') || document.title;
      var description = document.querySelector('meta[name=description]');
      if (description) description.setAttribute('content', wrapper.getAttribute('data-pjax-description') || '');
      var crumb = document.querySelector('.admin-breadcrumb');
      if (crumb) crumb.textContent = 'مدیریت / ' + document.title;
      if (push) history.pushState({yiiStorePjax:true,url:res.finalUrl},'',res.finalUrl);
      window.scrollTo({top:0,behavior:'instant'});
      target.focus({preventScroll:true});
      document.dispatchEvent(new CustomEvent('yii-store:pjax-loaded'));
    }).catch(function(err) {
      if (err.name === 'AbortError') return;
      // Progressive fallback if network, response or session/auth state differs.
      location.assign(url);
    }).finally(function() {
      if(window.ShopUI) window.ShopUI.progress(false);
      if (counter === requestNumber) target.classList.remove('loading-pjax');
    });
  }

  document.addEventListener('click',function(event) {
    var gallery = event.target.closest('[data-gallery-src]');
    if (gallery) {
      var main = document.getElementById('main-product-image');
      if (main) { main.src = gallery.getAttribute('data-gallery-src'); main.removeAttribute('srcset'); }
      return;
    }
    var a = event.target.closest('a[href]');
    if (!a || event.defaultPrevented || event.button!==0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (a.hasAttribute('download') || a.hasAttribute('data-no-pjax') || a.target && a.target !== '_self') return;
    if (!(a.closest('#pjax-main') || a.getAttribute('data-pjax')==='1')) return;
    var url;
    try { url = new URL(a.href,location.href); } catch(e) { return; }
    if (url.origin!==location.origin || !/^https?:$/.test(url.protocol) || url.pathname===location.pathname && url.search===location.search && url.hash) return;
    if (url.pathname.indexOf('/uploads/')!==-1 || url.pathname.indexOf('/assets/')!==-1) return;
    event.preventDefault(); navigate(url.href,true);
  });
  document.addEventListener('submit',function(event) {
    var form=event.target;
    if(event.defaultPrevented) return;
    if (form.getAttribute('data-pjax-form')!=='1' || form.method.toUpperCase()!=='GET') return;
    event.preventDefault();
    var url=new URL(form.action,location.href);
    new FormData(form).forEach(function(value,key){url.searchParams.set(key,value);});
    navigate(url.href,true);
  });
  window.addEventListener('popstate',function() { navigate(location.href,false); });
})();
