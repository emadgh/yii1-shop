const {chromium}=require('C:/Users/ToosArax/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const {spawn}=require('node:child_process');
const root=path.resolve(__dirname,'..');
const fixture=path.join(root,'protected/runtime/error-test-router.php');
const base='http://127.0.0.1:18087';
const router=`<?php
$root=dirname(__DIR__,2);
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(strpos($path,'/assets/')===0) return false;
if(strpos($path,'/test-error/')!==0) { require $root.'/public/index.php'; return; }
require $root.'/vendor/yiisoft/yii/framework/yii.php';
$config=require $root.'/protected/config/main.php';
// Every injected error must render even with an unusable database.
$config['components']['db']['connectionString']='sqlite:/nonexistent/error-test.sqlite';
$app=Yii::createWebApplication($config);
$app->onBeginRequest=function() use ($path,$app) {
    if($path==='/test-error/request') {
        unset($_SERVER['SCRIPT_NAME'],$_SERVER['PHP_SELF'],$_SERVER['ORIG_SCRIPT_NAME']);
        $_SERVER['SCRIPT_FILENAME']='/missing-entry.php';
        $app->request->getScriptUrl();
    }
    if($path==='/test-error/database') { $app->db->active=true; }
    if($path==='/test-error/php') { trigger_error('PRIVATE ERROR MESSAGE',E_USER_WARNING); }
    $code=(int)basename($path);
    throw new CHttpException($code>=400 ? $code : 500,'PRIVATE ERROR MESSAGE <script>alert(1)</script>');
};
$app->run();
`;
(async()=>{
 fs.writeFileSync(fixture,router);
 const server=spawn(process.env.PHP_BINARY||'php',['-S','127.0.0.1:18087','-t','public',fixture],{cwd:root,windowsHide:true,stdio:'ignore'});
 let browser;
 try {
  for(let i=0;i<60;i++) {try{await fetch(base+'/assets/fonts/Vazir-Regular.woff2');break;}catch{await new Promise(r=>setTimeout(r,100));}}
  browser=await chromium.launch({headless:true,executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe'});
  const page=await browser.newPage({viewport:{width:1440,height:900}});
  const errors=[];page.on('pageerror',e=>errors.push(e.message));
  for(const code of [400,403,404,405,429,500,503]) {
   const response=await page.goto(base+'/test-error/'+code);
   assert.equal(response.status(),code);
   assert.equal(await page.locator('h1').count(),1);
   assert.equal(await page.locator('html').getAttribute('dir'),'rtl');
   assert(!(await page.content()).includes('PRIVATE ERROR MESSAGE'));
   assert.equal(response.headers()['cache-control'],'no-store');
  }
  for(const scenario of ['request','database','php']) {
   const response=await page.goto(base+'/test-error/'+scenario);
   assert.equal(response.status(),500,scenario);
   assert.equal(await page.locator('.error-number').textContent(),'500');
   assert(!(await page.content()).includes('CHttpRequest'),scenario);
  }
  console.log('PASS HTTP statuses, PHP errors, broken request URL, broken database and hidden private details');
  const missing=await page.goto(base+'/missing-error-test-page');assert.equal(missing.status(),404);
  const direct=await page.goto(base+'/site/error');assert.equal(direct.status(),404);
  console.log('PASS real missing route and direct error action');
  for(const [width,height] of [[1440,900],[1366,768],[768,1024],[390,844],[320,740]]) {
   await page.setViewportSize({width,height});
   await page.goto(base+'/test-error/500');await page.evaluate(()=>document.fonts.ready);
   assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth),'horizontal overflow at '+width);
   assert(await page.locator('.button-primary').isVisible());
   assert(await page.evaluate(()=>document.fonts.check('14px Vazir')));
   if(width===1440||width===390) await page.screenshot({path:path.join(root,'protected/runtime/error-'+width+'.png'),fullPage:true});
  }
  const before=await page.locator('.floating-parcel').evaluate(e=>getComputedStyle(e).transform);
  await page.waitForTimeout(180);
  const after=await page.locator('.floating-parcel').evaluate(e=>getComputedStyle(e).transform);
  assert.notEqual(before,after,'SVG must animate');
  await page.emulateMedia({reducedMotion:'reduce'});
  assert.equal(await page.locator('.floating-parcel').evaluate(e=>getComputedStyle(e).animationName),'none');
  await page.locator('.button-secondary').click();assert.equal(new URL(page.url()).pathname,'/shop');
  await page.goto(base+'/test-error/404');await page.locator('.button-primary').click();assert.equal(new URL(page.url()).pathname,'/');
  assert.deepEqual(errors,[]);
  console.log('PASS five viewport sizes, local font, SVG animation, reduced motion and navigation');
 } finally {if(browser)await browser.close();server.kill();fs.unlinkSync(fixture);}
})().catch(e=>{console.error(e);process.exitCode=1;});
