const {chromium,launchOptions,php,phpArgs}=require('./test-runtime.cjs');
const {spawnSync}=require('node:child_process'),assert=require('node:assert/strict'),path=require('node:path');
const base=process.env.SHOP_TEST_BASE,root=path.resolve(__dirname,'..');
if(!base||!/^sqlite:.*[/\\]cms-browser-\d+\.sqlite$/.test(process.env.SHOP_DSN||''))throw Error('Disposable fixture required');
function run(code){const r=spawnSync(php,phpArgs.concat(['-r','require "tools/bootstrap.php";'+code]),{cwd:root,env:process.env,encoding:'utf8'});assert.equal(r.status,0,String(r.error||r.stderr||r.stdout));return r.stdout;}
(async()=>{const browser=await chromium.launch(launchOptions);try{
 run("Yii::app()->db->createCommand()->delete('login_attempts');");
 const ids=JSON.parse(run("$ids=array();foreach(array('header','footer') as $location)foreach(array('a','b','child') as $key){$m=new MenuItem;$m->label='Widget '.$location.' '.$key;$m->url='/shop';$m->location=$location;$m->is_active=1;$m->sort_order=100;$m->parent_id=$key==='child'?$ids[$location.'a']:null;if(!Hierarchy::save($m))throw new Exception(json_encode($m->errors));$ids[$location.$key]=(int)$m->id;}echo json_encode($ids);"));
 const page=await browser.newPage({viewport:{width:1440,height:1000}}),errors=[];page.on('pageerror',e=>errors.push(e.message));
 async function go(url){const r=await page.goto(base+url);assert.equal(r.status(),200,url);await page.waitForLoadState('networkidle');return r;}
 async function settled(){await page.waitForFunction(()=>!document.querySelector('.section-is-busy'));await page.waitForLoadState('networkidle');}
 async function password(p,password='BazaarcheTest123!'){await p.goto(base+'/login');assert.equal(await p.locator('#otp').count(),0);await p.locator('#email').fill('demo@bazaarche.test');await p.locator('#password').fill(password);await p.getByRole('button',{name:'ورود به داشبورد',exact:true}).click();await p.waitForLoadState('networkidle');}
 await password(page);await page.waitForURL('**/admin');
 await go('/adminMenu/create');await page.locator('#MenuItem_label').fill('Created widget link');await page.locator('#MenuItem_url').fill('/shop');await page.getByRole('button',{name:'ذخیره لینک',exact:true}).click();await settled();assert.equal(await page.locator('#menu-form .errorSummary').count(),0);assert(new URL(page.url()).pathname==='/adminMenu/index',page.url());
 await go('/adminMenu/index?location=header&id='+ids.headera);
 assert.equal(await page.locator('.menu-location-tabs [aria-current=page]').textContent(),'بالای سایت');
 assert.equal(await page.locator('[data-cat-id="'+ids.footera+'"]').count(),0);
 assert.equal(await page.locator('#MenuItem_parent_id option[value="'+ids.footera+'"]').count(),0);
 await page.locator('#MenuItem_label').fill('Unsaved menu label');
 await page.locator('[data-cat-id="'+ids.headera+'"]').evaluate(n=>n.scrollIntoView({block:'center',behavior:'instant'}));
 const source=await page.locator('[data-drag-category="'+ids.headera+'"]').boundingBox(),target=await page.locator('[data-cat-id="'+ids.headerb+'"] [data-tree-row]').boundingBox();
 await page.mouse.move(source.x+source.width/2,source.y+source.height/2);await page.mouse.down();await page.mouse.move(target.x+target.width/2,target.y+target.height/2,{steps:16});await page.mouse.up();await settled();
 assert.equal(await page.locator('[data-cat-id="'+ids.headera+'"]').getAttribute('data-parent'),String(ids.headerb));
 assert.equal(await page.locator('#MenuItem_label').inputValue(),'Unsaved menu label');
 assert.equal(await page.locator('#MenuItem_parent_id').inputValue(),String(ids.headerb));
 assert.equal(run('echo MenuItem::model()->findByPk('+ids.headerchild+')->parent_id;'),String(ids.headera));
 const csrf=await page.locator('meta[name=csrf-token]').getAttribute('content'),revision=await page.locator('#menu-tree').getAttribute('data-tree-revision');
 async function move(data){return page.request.post(base+'/adminMenu/move?location=header',{form:{YII_CSRF_TOKEN:csrf,revision,position:'inside',...data}});}
 assert.equal((await move({id:ids.headera,target:ids.footera})).status(),422);
 assert.equal((await move({id:ids.footera,target:ids.headera})).status(),422);
 assert.equal((await move({id:ids.headerb,target:ids.headerchild})).status(),422);
 assert.equal((await move({id:ids.headera,target:ids.headerb,revision:'stale'})).status(),409);
 const forged=await page.request.post(base+'/adminMenu/index?location=header&id='+ids.headera,{form:{YII_CSRF_TOKEN:csrf,'MenuItem[location]':'footer','MenuItem[label]':'forged',tree_revision:revision}});assert.equal(forged.status(),422);
 await page.locator('#menu-form').evaluate(n=>n.requestSubmit());await settled();assert.equal(run('echo MenuItem::model()->findByPk('+ids.headera+')->label;'),'Unsaved menu label');
 await page.screenshot({path:'protected/runtime/menu-hierarchy.png',fullPage:true});
 await page.locator('.menu-location-tabs a').filter({hasText:'پایین سایت'}).click();await settled();
 assert.equal(await page.locator('[data-cat-id="'+ids.headera+'"]').count(),0);assert.equal(await page.locator('[data-cat-id="'+ids.footera+'"]').count(),1);assert.equal(await page.locator('#MenuItem_location').inputValue(),'footer');
 await page.locator('[data-cat-id="'+ids.footera+'"] .category-edit-link').click();await settled();assert.equal(await page.locator('#MenuItem_label').inputValue(),'Widget footer a');
 await page.setViewportSize({width:390,height:844});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));await page.setViewportSize({width:1440,height:1000});
 console.log('PASS shared menu widget: scoped tabs, real subtree drag, dirty form retention, Ajax save, cycle/scope/stale guards and mobile layout');
 const response=await go('/adminSecurity/index');assert.match(response.headers()['cache-control'],/no-store/);
 const qr=page.locator('img.authenticator-qr');assert.match(await qr.getAttribute('src'),/^data:image\/svg\+xml;base64,/);await qr.screenshot({path:'protected/runtime/authenticator-qr-test.png'});
 const secret=(await page.locator('code').textContent()).trim();
 const uri=run("echo (new \\chillerlan\\QRCode\\QRCode)->readFromFile('protected/runtime/authenticator-qr-test.png')->data;");
 assert(uri.startsWith('otpauth://totp/'));const params=new URL(uri).searchParams;assert.equal(params.get('secret'),secret);assert.equal(params.get('digits'),'6');assert.equal(params.get('period'),'30');assert(params.get('issuer'));
 const code=run('echo TwoFactor::code('+JSON.stringify(secret)+',intdiv(time(),30));');await page.locator('#password').fill('BazaarcheTest123!');await page.locator('#code').fill(code);await page.getByRole('button',{name:'فعال‌کردن',exact:true}).click();await page.waitForLoadState('networkidle');assert.equal(await page.locator('.authenticator-qr').count(),0);
 const guest=await browser.newContext(),p=await guest.newPage();await password(p,'wrong-password');assert.equal(await p.locator('#otp').count(),0);assert.equal(new URL(p.url()).pathname,'/login');
 await password(p);await p.waitForURL('**/site/verify');assert.equal(await p.locator('#password').count(),0);
 const denied=await guest.request.get(base+'/adminMenu/index',{maxRedirects:0});assert.equal(denied.status(),302,'password alone does not authenticate');
 const invalid=run('echo TwoFactor::code('+JSON.stringify(secret)+',intdiv(time(),30)+10);');await p.locator('#otp').fill(invalid);await p.getByRole('button',{name:'تأیید و ورود',exact:true}).click();await p.waitForLoadState('networkidle');assert.equal(new URL(p.url()).pathname,'/site/verify');
 const valid=run('echo TwoFactor::code('+JSON.stringify(secret)+',intdiv(time(),30));');await p.locator('#otp').fill(valid);await p.getByRole('button',{name:'تأیید و ورود',exact:true}).click();await p.waitForURL('**/admin');
 const retry=await browser.newContext(),rp=await retry.newPage();await password(rp);await rp.waitForURL('**/site/verify');for(let i=0;i<5;i++){await rp.locator('#otp').fill(valid);await rp.getByRole('button',{name:'تأیید و ورود',exact:true}).click();await rp.waitForLoadState('networkidle');}assert.equal(new URL(rp.url()).pathname,'/login');
 assert.equal((await retry.request.get(base+'/site/verify',{maxRedirects:0})).status(),302,'exhausted challenge cannot be reused');
 run("Yii::app()->db->createCommand()->update('users',array('totp_secret'=>''),'email=:e',array(':e'=>'demo@bazaarche.test'));Yii::app()->db->createCommand()->delete('login_attempts');");
 assert.deepEqual(errors,[]);console.log('PASS locally decoded QR provisioning, password-first login, guest isolation, invalid OTP, valid OTP, replay rejection and five-attempt limit');
 }finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1});
