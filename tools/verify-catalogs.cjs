const {chromium,launchOptions}=require('./test-runtime.cjs');
const assert=require('node:assert/strict');
const fs=require('node:fs'),path=require('node:path');
if(!/^sqlite:.*[/\\]cms-browser-\d+\.sqlite$/.test(process.env.SHOP_DSN||''))throw Error('Disposable browser fixture required');
(async()=>{
 const browser=await chromium.launch(launchOptions),base=process.env.SHOP_TEST_BASE||'http://127.0.0.1:8091';
 const context=await browser.newContext(),page=await context.newPage({viewport:{width:1440,height:1000}}),errors=[];page.on('pageerror',e=>errors.push(e.message));
 let pdfId,catalogId;
 async function go(route){const r=await page.goto(base+route);assert.equal(r.status(),200,route);await page.waitForLoadState('networkidle');}
 async function post(route,data){const token=await page.locator('meta[name=csrf-token]').getAttribute('content');return context.request.post(base+route,{form:{...data,YII_CSRF_TOKEN:token}});}
 try{
  await go('/login');await page.locator('#email').fill('demo@bazaarche.test');await page.locator('#password').fill('BazaarcheTest123!');await page.getByRole('button',{name:'ورود به داشبورد',exact:true}).click();await page.waitForURL('**/admin');
  await go('/adminCatalog/create');await page.locator('#CompanyCatalog_title').fill('کاتالوگ مرورگر');await page.locator('#CompanyCatalog_description').fill('**معرفی شرکت**\n\nتوضیحات کاتالوگ تست');await page.locator('#CompanyCatalog_is_active').check();
  const imagePicker=page.locator('.media-picker').filter({has:page.locator('[name=image_id]')});await imagePicker.locator('[data-open-media]').click();await page.locator('[data-media-scope=all]').click();await page.waitForResponse(r=>r.url().includes('/adminMedia/list?')&&r.url().includes('scope=all'));
  const imageRows=await context.request.get(base+'/adminMedia/list?accept=image%2F*&scope=all');const images=await imageRows.json();assert(images.items.length);assert(images.items.every(x=>x.mime.startsWith('image/')));
  await page.locator('#media-grid .media-tile-select').first().click();await page.locator('#media-apply').click();assert(await imagePicker.locator('.media-value').inputValue());
  const pdfPicker=page.locator('.media-picker').filter({has:page.locator('[name=pdf_id]')});await pdfPicker.locator('[data-open-media]').click();assert.equal(await page.locator('#media-upload').getAttribute('accept'),'application/pdf');assert.equal(await page.locator('[data-media-scope=catalogs]').getAttribute('aria-selected'),'true');
  const upload=page.waitForResponse(r=>r.url().endsWith('/adminMedia/upload'));
  await page.locator('#media-upload').setInputFiles({name:'browser-catalog.pdf',mimeType:'application/pdf',buffer:Buffer.from('%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n')});
  const uploadResult=await (await upload).json();assert(uploadResult.ok);pdfId=uploadResult.item.id;assert.equal(uploadResult.item.upload_scope,'catalogs');await page.locator('#media-apply').click();assert.equal(await pdfPicker.locator('.media-value').inputValue(),String(pdfId));
  const listed=await (await context.request.get(base+'/adminMedia/list?accept=application%2Fpdf&scope=catalogs')).json();assert(listed.items.some(x=>x.id===pdfId));assert(listed.items.every(x=>x.mime==='application/pdf'&&x.upload_scope==='catalogs'));
  const token=await page.locator('meta[name=csrf-token]').getAttribute('content');const invalid=await context.request.post(base+'/adminMedia/upload',{multipart:{YII_CSRF_TOKEN:token,scope:'catalogs',accept:'application/pdf',file:{name:'wrong.txt',mimeType:'text/plain',buffer:Buffer.from('Not a PDF')}}});assert.equal(invalid.status(),422);
  await page.getByRole('button',{name:'ذخیره کاتالوگ',exact:true}).click();await page.waitForURL('**/adminCatalog/index');await page.getByRole('link',{name:'ویرایش ←'}).first().click();await page.waitForURL('**/adminCatalog/update*');catalogId=new URL(page.url()).searchParams.get('id');assert(catalogId);
  await page.locator('#CompanyCatalog_title').fill('کاتالوگ مرورگر ویرایش شده');await page.getByRole('button',{name:'ذخیره کاتالوگ',exact:true}).click();await page.waitForURL('**/adminCatalog/index');
  await go('/catalogs');const card=page.locator('.company-catalog').filter({hasText:'کاتالوگ مرورگر ویرایش شده'});assert.equal(await card.count(),1);assert.equal(await card.locator('img').count(),1);assert.equal(await card.locator('strong').textContent(),'معرفی شرکت');
  const guest=await browser.newContext();const download=await guest.request.get(base+'/catalog/download?id='+catalogId);assert.equal(download.status(),200);assert.match(download.headers()['content-type'],/application\/pdf/);assert((await download.body()).subarray(0,5).equals(Buffer.from('%PDF-')));await guest.close();
  const protectedDelete=await post('/adminMedia/delete?id='+pdfId,{});assert.equal(protectedDelete.status(),422);
  await page.setViewportSize({width:390,height:844});await go('/catalogs');assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1));await page.screenshot({path:path.resolve('protected/runtime/company-catalogs-mobile.png'),fullPage:true});
  await go('/adminCatalog/update?id='+catalogId);await page.locator('#CompanyCatalog_is_active').uncheck();await page.getByRole('button',{name:'ذخیره کاتالوگ',exact:true}).click();await page.waitForURL('**/adminCatalog/index');await go('/catalogs');assert.equal(await page.locator('.company-catalog').filter({hasText:'کاتالوگ مرورگر ویرایش شده'}).count(),0);assert.equal((await context.request.get(base+'/catalog/download?id='+catalogId)).status(),404);
  assert.equal((await context.request.get(base+'/adminCatalog/delete?id='+catalogId)).status(),405);assert.equal((await context.request.post(base+'/adminCatalog/delete?id='+catalogId,{form:{}})).status(),400);
  await page.setViewportSize({width:1440,height:1000});await go('/adminSettings/index');await page.locator('#cache_backend').selectOption('file');await page.locator('#cache_ttl').fill('120');await page.getByRole('button',{name:'ذخیره تنظیمات',exact:true}).click();await page.waitForLoadState('networkidle');assert.equal(await page.locator('#cache_backend').inputValue(),'file');await go('/catalogs');assert.equal((await post('/adminSettings/clearCache',{})).status(),200);await go('/adminSettings/index');await page.locator('#cache_backend').selectOption('db');await page.getByRole('button',{name:'ذخیره تنظیمات',exact:true}).click();await page.waitForLoadState('networkidle');
  assert.equal(errors.length,0,errors.join('\n'));console.log('PASS catalog CRUD, scoped media dialog, strict PDF upload/filter, public download, unpublish cache invalidation, CSRF, responsive layout and cache settings');
 }finally{
  if(catalogId)await post('/adminCatalog/delete?id='+catalogId,{});
  if(pdfId)await post('/adminMedia/delete?id='+pdfId,{});
  await browser.close();
 }
})().catch(e=>{console.error(e);process.exitCode=1;});
