const {chromium,launchOptions,php,phpArgs}=require('./test-runtime.cjs');
const {spawnSync}=require('node:child_process'),fs=require('node:fs'),assert=require('node:assert/strict');
if(!/^sqlite:.*[/\\]cms-browser-\d+\.sqlite$/.test(process.env.SHOP_DSN||''))throw Error('Disposable fixture required');
const base=process.env.SHOP_TEST_BASE;
function query(code){const r=spawnSync(php,[...phpArgs,'-r','require "tools/bootstrap.php";'+code],{env:process.env,encoding:'utf8'});assert.equal(r.status,0,r.stderr||r.stdout);return r.stdout;}
(async()=>{
 query('Yii::app()->db->createCommand()->delete("login_attempts");');
 const browser=await chromium.launch(launchOptions);
 try{
  const page=await browser.newPage({viewport:{width:1440,height:1000}}),errors=[];page.on('pageerror',e=>errors.push(e.message));
  const go=async route=>{assert.equal((await page.goto(base+route)).status(),200);await page.waitForLoadState('networkidle');};
  await go('/login');await page.locator('#email').fill('demo@bazaarche.test');await page.locator('#password').fill('BazaarcheTest123!');await page.getByRole('button',{name:'ورود به داشبورد',exact:true}).click();await page.waitForURL('**/admin');
  await go('/adminProduct/create');await page.locator('#Product_title').fill('Media queue product');await page.locator('#Product_sku').fill('MEDIA-QUEUE');await page.locator('#Product_slug').fill('media-queue');
  const draft=await page.locator('[name=media_context]').inputValue();assert.match(draft,/^product:draft:/);
  await page.locator('[data-open-media]').first().click();await page.locator('[data-media-scope=context]').click();await page.locator('.media-empty').waitFor();
  let release;const gate=new Promise(resolve=>release=resolve);let uploads=0;
  await page.route('**/adminMedia/upload',async route=>{uploads++;if(uploads===1)await gate;await route.continue();});
  const buffer=fs.readFileSync('public/assets/storefront/product-lamp.webp');
  await page.locator('#media-upload').setInputFiles(['one','cancel','three'].map(name=>({name:'queue-'+name+'.webp',mimeType:'image/webp',buffer})));
  await page.locator('[data-upload-status=uploading]').waitFor();assert.equal(await page.locator('[data-upload-status=queued]').count(),2);assert.equal(await page.locator('.media-upload-job img').count(),3);
  await page.getByRole('button',{name:'حذف از صف queue-cancel.webp',exact:true}).click();assert.equal(await page.locator('.media-upload-job').count(),2);
  await page.evaluate(()=>document.getElementById('product-form').requestSubmit(document.querySelector('[name=save_intent][value=draft]')));
  assert.equal(await page.locator('#product-form .section-loading-overlay').count(),0,'saving during upload does not lock the form');assert.equal(Number(query('echo Product::model()->countByAttributes(array("sku"=>"MEDIA-QUEUE"));')),0);
  await page.screenshot({path:'protected/runtime/media-upload-queue.png'});release();
  await page.waitForFunction(()=>document.querySelectorAll('[data-upload-status=done]').length===2);await page.waitForFunction(()=>document.querySelector('#media-grid').getAttribute('aria-busy')===null);assert.equal(uploads,2,'cancelled file never reaches server');
  assert.equal(await page.locator('#media-grid .media-tile-select').count(),2);await page.locator('#media-deselect').click();assert.match(await page.locator('#media-count').textContent(),/^0 /);assert.equal(await page.locator('#media-grid [aria-pressed=true]').count(),0);
  await page.locator('#media-grid .media-tile-select').first().click();await page.locator('#media-apply').click();assert.equal(await page.locator('[data-album-row] .selected-media').count(),1);
  await page.locator('#Product_sku').fill(query('echo Product::model()->findByPk(1)->sku;'));await page.getByRole('button',{name:'ذخیره پیش‌نویس',exact:true}).click();await page.locator('#product-form .errorSummary').waitFor();assert.equal(await page.locator('[name=media_context]').inputValue(),draft,'failed save preserves draft upload context');
  assert.equal(Number(query('echo Yii::app()->db->createCommand("SELECT COUNT(*) FROM media WHERE upload_context=\''+draft+'\'")->queryScalar();')),2);
  await page.locator('#Product_sku').fill('MEDIA-QUEUE');await page.getByRole('button',{name:'ذخیره پیش‌نویس',exact:true}).click();await page.waitForURL('**/adminProduct/update?id=*');const productId=new URL(page.url()).searchParams.get('id');
  assert.equal(Number(query('echo Yii::app()->db->createCommand("SELECT COUNT(*) FROM media WHERE upload_context=\'product:'+Number(productId)+'\'")->queryScalar();')),2,'all uploads claimed even if not selected');
  await page.locator('[data-open-media]').first().click();await page.locator('[data-media-scope=context]').click();await page.waitForFunction(()=>document.querySelectorAll('#media-grid .media-tile-select').length===2);await page.locator('[data-close-media]').first().click();
  await go('/adminProduct/update?id=1');await page.locator('[data-open-media]').first().click();await page.locator('[data-media-scope=context]').click();await page.locator('.media-empty').waitFor();await page.locator('[data-media-scope=all]').click();await page.waitForFunction(()=>document.querySelectorAll('#media-grid .media-tile-select').length>2);await page.locator('[data-close-media]').first().click();
  console.log('PASS instant image queue, per-file status, queued cancellation, save guard, deselect, draft claim and cross-product upload isolation');
  await go('/adminPost/create');await page.locator('#Post_title').fill('Media context post');await page.locator('#Post_slug').fill('media-context-post');
  await page.locator('.editor-toolbar button').filter({hasText:'رسانه'}).first().click();await page.locator('[data-media-scope=context]').click();await page.locator('.media-empty').waitFor();
  const response=page.waitForResponse(r=>r.url().endsWith('/adminMedia/upload')&&r.request().method()==='POST');await page.locator('#media-upload').setInputFiles({name:'post-own.webp',mimeType:'image/webp',buffer});const postMedia=(await (await response).json()).item;
  await page.waitForFunction(()=>document.querySelector('[data-upload-status=done]'));await page.locator('#media-apply').click();assert.match(await page.locator('#Post_excerpt').inputValue(),/!\[/);
  await page.getByRole('button',{name:'ذخیره نوشته',exact:true}).click();await page.waitForURL('**/adminPost/index');
  const postId=Number(query('echo Post::model()->findByAttributes(array("slug"=>"media-context-post"))->id;'));assert.equal(query('echo MediaRepository::find('+Number(postMedia.id)+')["upload_context"];'),'post:'+postId);
  await go('/adminPost/update?id='+postId);await page.locator('[data-open-media]').click();await page.locator('[data-media-scope=context]').click();await page.waitForFunction(()=>document.querySelectorAll('#media-grid .media-tile-select').length===1);await page.locator('#media-deselect').click();await page.locator('#media-apply').click();assert.equal(await page.locator('[name=cover_media]').inputValue(),'');
  const invalid=await page.request.get(base+'/adminMedia/list?scope=context&context=product:draft:'+('a'.repeat(32)));assert.equal(invalid.status(),403);
  const missing=await page.request.get(base+'/adminMedia/list?scope=context');assert.equal(missing.status(),400);
  await page.setViewportSize({width:390,height:844});await page.locator('[data-open-media]').click();assert(await page.locator('#media-dialog').evaluate(el=>el.scrollWidth<=el.clientWidth));await page.screenshot({path:'protected/runtime/media-context-mobile.png'});
  assert.deepEqual(errors,[]);console.log('PASS blog editor uploads share record context, scope validation, single picker deselect and responsive dialog');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
