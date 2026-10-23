(function(){'use strict';
document.querySelectorAll('[data-mcp-setup]').forEach(function(root){
  requestAnimationFrame(function(){root.tabIndex=-1;root.focus({preventScroll:true});root.scrollIntoView({block:'start'});});
  var direct=root.querySelector('[data-mcp-direct]'),privateConfig=root.querySelector('[data-mcp-private]'),path=root.querySelector('[data-mcp-project-path]'),privatePath=root.querySelector('[data-mcp-private-path]'),status=root.querySelector('[data-mcp-copy-status]');
  function update(){var project=path.value.trim().replace(/\\/g,'/').replace(/\/+$/,'');var a=JSON.parse(direct.value),b=JSON.parse(privateConfig.value);a.mcpServers.bazaarche.args=[project+'/integrations/ai/mcp-server.mjs'];b.mcpServers.bazaarche.args=a.mcpServers.bazaarche.args;b.mcpServers.bazaarche.env.BAZAARCHE_TOKEN_FILE=privatePath.value.trim().replace(/\\/g,'/');direct.value=JSON.stringify(a,null,2);privateConfig.value=JSON.stringify(b,null,2);}
  path.addEventListener('input',update);privatePath.addEventListener('input',update);
  root.addEventListener('click',async function(event){var button=event.target.closest('[data-mcp-copy]');if(!button)return;var field=document.getElementById(button.dataset.mcpCopy);try{if(navigator.clipboard&&window.isSecureContext)await navigator.clipboard.writeText(field.value);else{field.focus();field.select();if(!document.execCommand('copy'))throw Error('copy');}status.textContent='کپی شد؛ آن را در محل خصوصی کلاینت ذخیره کنید.';}catch(error){field.focus();field.select();status.textContent='متن انتخاب شد؛ با Ctrl+C یا فرمان Copy کپی کنید.';}});
  // Browser back/forward cache must not retain an issuing response's credentials.
  window.addEventListener('pagehide',function(){root.querySelectorAll('input,textarea').forEach(function(field){field.value='';});});
  window.addEventListener('pageshow',function(event){if(event.persisted)window.location.reload();});
});
})();
