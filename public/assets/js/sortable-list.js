(function(){'use strict';
var drag=null,ghost=null;
function items(list){return Array.from(list.children).filter(function(n){return n.matches('[data-sortable-item]');});}
function refresh(list){var rows=items(list);rows.forEach(function(row,i){row.querySelectorAll('[data-sort-step]').forEach(function(b){b.disabled=b.dataset.sortStep==='before'?i===0:i===rows.length-1;});});}
function changed(list){refresh(list);list.dispatchEvent(new CustomEvent('sortchange',{bubbles:true}));}
function clear(){if(drag){items(drag.list).forEach(function(n){n.classList.remove('sort-drop-before','sort-drop-after','sort-is-dragging');});}if(ghost)ghost.remove();ghost=null;}
function finish(){clear();drag=null;}
document.addEventListener('pointerdown',function(e){var handle=e.target.closest('[data-sort-handle]');if(!handle||e.button!==0)return;var row=handle.closest('[data-sortable-item]'),list=row&&row.parentElement;if(!list||!list.matches('[data-sortable-list]'))return;drag={row:row,list:list,x:e.clientX,y:e.clientY,started:false,target:null};handle.setPointerCapture(e.pointerId);});
document.addEventListener('pointermove',function(e){if(!drag)return;if(!drag.row.isConnected){finish();return;}if(!drag.started&&Math.hypot(e.clientX-drag.x,e.clientY-drag.y)<7)return;if(!drag.started){drag.started=true;ghost=document.createElement('div');ghost.className='sortable-ghost';ghost.textContent=(drag.row.querySelector('[data-sort-title]')||drag.row).textContent.slice(0,150);document.body.append(ghost);}e.preventDefault();ghost.style.left=e.clientX+14+'px';ghost.style.top=e.clientY+14+'px';items(drag.list).forEach(function(n){n.classList.remove('sort-drop-before','sort-drop-after');});drag.row.classList.add('sort-is-dragging');
 var node=document.elementFromPoint(e.clientX,e.clientY),target=node&&node.closest('[data-sortable-item]');drag.target=null;
 if(target&&target!==drag.row&&target.parentElement===drag.list){var rect=target.getBoundingClientRect();drag.after=e.clientY>rect.top+rect.height/2;drag.target=target;target.classList.add(drag.after?'sort-drop-after':'sort-drop-before');}
 if(e.clientY<65)window.scrollBy({top:-18,behavior:'instant'});else if(e.clientY>innerHeight-65)window.scrollBy({top:18,behavior:'instant'});
},{passive:false});
document.addEventListener('pointerup',function(){if(!drag)return;var state=drag;finish();if(state.started&&state.target){state.list.insertBefore(state.row,state.after?state.target.nextSibling:state.target);changed(state.list);}});
document.addEventListener('pointercancel',finish);document.addEventListener('keydown',function(e){if(e.key==='Escape')finish();});
document.addEventListener('click',function(e){var button=e.target.closest('[data-sort-step]');if(!button)return;var row=button.closest('[data-sortable-item]'),list=row&&row.parentElement;if(!list||!list.matches('[data-sortable-list]'))return;var rows=items(list),index=rows.indexOf(row),target=rows[index+(button.dataset.sortStep==='before'?-1:1)];if(target){list.insertBefore(row,button.dataset.sortStep==='before'?target:target.nextSibling);changed(list);}});
function init(){if(drag&&!drag.row.isConnected)finish();document.querySelectorAll('[data-sortable-list]').forEach(refresh);}
window.ShopSortable={refresh:refresh};document.addEventListener('yii-store:pjax-loaded',init);init();
})();
