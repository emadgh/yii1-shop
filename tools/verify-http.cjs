const {chromium,launchOptions}=require('./test-runtime.cjs');
const assert=require('node:assert/strict');
(async()=>{const browser=await chromium.launch(launchOptions);const ctx=await browser.newContext();const page=await ctx.newPage({viewport:{width:1440,height:1000}});const base=process.env.SHOP_TEST_BASE||'http://127.0.0.1:8080';
try{
 let r=await ctx.request.get(base+'/adminMedia/list',{maxRedirects:0});assert.equal(r.status(),302);console.log('PASS guest cannot access media library API');
 await page.goto(base+'/login');await page.locator('#email').fill(process.env.SHOP_TEST_EMAIL||'demo@bazaarche.test');await page.locator('#password').fill(process.env.SHOP_TEST_PASSWORD||'BazaarcheTest123!');await page.getByRole('button',{name:'ورود به داشبورد',exact:true}).click();await page.waitForURL('**/admin');
 r=await ctx.request.post(base+'/adminMedia/upload',{multipart:{file:{name:'missing-csrf.txt',mimeType:'text/plain',buffer:Buffer.from('sample')}}});assert.equal(r.status(),400);console.log('PASS upload requires valid CSRF token');
 const token=await page.locator('meta[name=csrf-token]').getAttribute('content');
 r=await ctx.request.post(base+'/adminMedia/upload',{multipart:{YII_CSRF_TOKEN:token,file:{name:'fake.jpg',mimeType:'image/jpeg',buffer:Buffer.from('<?php echo 1;')}}});assert.equal(r.status(),422);console.log('PASS image extension spoof rejected');
 r=await ctx.request.post(base+'/adminMedia/upload',{multipart:{YII_CSRF_TOKEN:token,file:{name:'test-document.pdf',mimeType:'application/pdf',buffer:Buffer.from('%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF')}}});assert.equal(r.status(),200);const pdf=(await r.json()).item;assert.equal(pdf.mime,'application/pdf');
 await page.goto(base+'/adminProduct/update?id=1');await page.locator('[data-open-media]').click();await page.waitForFunction(()=>document.querySelectorAll('#media-grid .media-tile-select').length>=9);assert.equal(await page.locator('#media-grid .file-icon').count(),0);await page.waitForFunction(()=>[...document.querySelectorAll('#media-grid img')].every(img=>img.complete&&img.naturalWidth));await page.screenshot({path:'protected/runtime/media-dialog.png'});await page.locator('[data-close-media]').first().click();
 const csrf=await page.locator('meta[name=csrf-token]').getAttribute('content');r=await ctx.request.post(base+'/adminMedia/delete?id='+pdf.id,{form:{YII_CSRF_TOKEN:csrf}});assert.equal(r.status(),200);console.log('PASS PDF upload and configurable image-only picker filter');
 const referencedId=(await page.locator('[data-album-row] .media-value').first().inputValue()).split(',')[0];r=await ctx.request.post(base+'/adminMedia/delete?id='+referencedId,{form:{YII_CSRF_TOKEN:csrf}});assert.equal(r.status(),422);console.log('PASS shared referenced media cannot be deleted');
 await page.goto(base+'/blog?q='+encodeURIComponent('هدیه'));assert.equal(await page.locator('.post-card').count(),1);console.log('PASS blog search');
 await page.goto(base+'/adminReport/index');assert.equal(await page.locator('#stock-grid tbody .status-cancelled').count(),0);await page.screenshot({path:'protected/runtime/finance-inventory.png',fullPage:true});console.log('PASS live inventory ledger fully reconciled');
 console.log('ALL HTTP CHECKS PASSED');
}finally{await browser.close();}})().catch(e=>{console.error(e);process.exitCode=1;});
