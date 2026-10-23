const {chromium,launchOptions,php,phpArgs}=require('./test-runtime.cjs');
const {spawnSync}=require('node:child_process'),assert=require('node:assert/strict');
if(!/^sqlite:.*[/\\]cms-browser-\d+\.sqlite$/.test(process.env.SHOP_DSN||''))throw Error('Disposable fixture required');
const base=process.env.SHOP_TEST_BASE;
function query(code){const r=spawnSync(php,[...phpArgs,'-r','require "tools/bootstrap.php";'+code],{env:process.env,encoding:'utf8'});assert.equal(r.status,0,r.stderr||r.stdout);return r.stdout;}
(async()=>{
 const fixture=JSON.parse(query(`
 Yii::app()->db->createCommand()->delete('login_attempts');
 $parent=new Category;$parent->name='دسته ویجت';$parent->slug='feed-parent';if(!$parent->save())throw new Exception('category');
 $child=new Category;$child->name='زیرشاخه ویجت';$child->slug='feed-child';$child->parent_id=$parent->id;if(!$child->save())throw new Exception('child');
 $ids=array();foreach(array('a','b','c','draft','variant') as $i=>$key){$p=new Product;$p->title='Feed '.$key;$p->sku='HOME-FEED-'.$key;$p->slug='home-feed-'.$key;$p->price=1000;$p->stock=0;$p->status=$key==='draft'?'draft':'published';$p->category_id=$key==='a'?$child->id:($key==='b'?$parent->id:null);if($key==='variant')$p->parent_product_id=$ids['a'];if(!$p->save())throw new Exception(CJSON::encode($p->errors));$ids[$key]=(int)$p->id;Yii::app()->db->createCommand()->update('products',array('created_at'=>'2030-01-0'.($i+1).' 00:00:00'),'id=:id',array(':id'=>$p->id));}
 Yii::app()->db->createCommand()->delete('page_visits');
 foreach(array('a'=>3,'b'=>5,'c'=>1,'draft'=>10,'variant'=>10) as $key=>$count)for($i=0;$i<$count;$i++)Yii::app()->db->createCommand()->insert('page_visits',array('event_id'=>'home-'.$key.'-'.$i,'visitor_hash'=>str_repeat('a',64),'path'=>'/product/home-feed-'.$key,'referrer_host'=>'','device'=>'desktop','created_at'=>date('Y-m-d H:i:s')));
 echo CJSON::encode(array('category'=>(int)$parent->id,'child'=>(int)$child->id,'products'=>$ids));`));
 const browser=await chromium.launch(launchOptions);
 try{
  const page=await browser.newPage({viewport:{width:1440,height:1000}}),errors=[];page.on('pageerror',e=>errors.push(e.message));
  const go=async route=>{assert.equal((await page.goto(base+route)).status(),200);await page.waitForLoadState('networkidle');};
  await go('/login');await page.locator('#email').fill('demo@bazaarche.test');await page.locator('#password').fill('BazaarcheTest123!');await page.getByRole('button',{name:'ورود به داشبورد',exact:true}).click();await page.waitForURL('**/admin');
  await go('/adminTheme/customize?key=market-light');
  for(const [type,title,layout] of [['latest_products','تازه‌های تست','grid'],['popular_products','پربازدیدهای تست','carousel'],['category_products','دسته تست','grid']]){
   await page.locator('[data-add-block]').click();const block=page.locator('[data-theme-block]').last();await block.locator('[data-block-type]').selectOption(type);
   assert.equal(await block.locator('[data-block-category]').isVisible(),type==='category_products');assert.equal(await block.locator('[data-block-collection]').isVisible(),false);
   await block.locator('input[name$="[title]"]').fill(title);await block.locator('select[name$="[presentation]"]').selectOption(layout);await block.locator('input[name$="[item_limit]"]').fill('2');
   if(type==='category_products')await block.locator('select[name$="[category_id]"]').selectOption(String(fixture.category));
  }
  await page.getByRole('button',{name:'ذخیره چیدمان قالب',exact:true}).click();await page.waitForLoadState('networkidle');
  assert.equal(await page.locator('[data-theme-block]').last().locator('select[name$="[category_id]"]').inputValue(),String(fixture.category));
  await page.screenshot({path:'protected/runtime/home-widgets-admin.png',fullPage:true});
  const stored=JSON.parse(query('echo CJSON::encode(ThemeOptions::blocks("market-light"));'));assert.equal(Number(stored.filter(b=>b.block_type==='category_products')[0].category_id),fixture.category);
  const names=async type=>page.locator('[data-product-feed="'+type+'"] .product-card h3').allTextContents();
  await go('/?_theme=market-light');assert.deepEqual(await names('latest_products'),['Feed c','Feed b']);assert.deepEqual(await names('popular_products'),['Feed b','Feed a']);assert.deepEqual(await names('category_products'),['Feed b','Feed a']);
  assert.equal(await page.locator('[data-product-feed="popular_products"] .product-carousel').count()+await page.locator('[data-product-feed="popular_products"] [data-rail-dir]').count()>0,true);
  assert.match(await page.locator('[data-product-feed="category_products"] .text-link').getAttribute('href'),/category\/feed-parent/);
  console.log('PASS widget editor persistence, latest order/limits, actual visit ranking, hierarchy filtering and draft/variant exclusion');
  query('Yii::app()->db->createCommand()->update("products",array("status"=>"draft"),"id=:id",array(":id"=>'+fixture.products.b+'));');
  await go('/?_theme=market-light');assert.deepEqual(await names('latest_products'),['Feed c','Feed a']);assert.deepEqual(await names('popular_products'),['Feed a','Feed c']);assert.deepEqual(await names('category_products'),['Feed a']);
  const rejected=query('try{ThemeOptions::save("market-light",ThemeOptions::get("market-light"),array(array("block_type"=>"category_products","category_id"=>999999)));echo "FAILED";}catch(Exception $e){echo "REJECTED";}');assert.equal(rejected,'REJECTED');
  assert.equal(JSON.parse(query('echo CJSON::encode(ThemeOptions::blocks("market-light"));')).length,stored.length,'invalid category leaves layout intact');
  query('foreach(array("market-dark","minimal-light","minimal-dark") as $key)ThemeOptions::save($key,ThemeOptions::get($key),ThemeOptions::blocks("market-light"));');
  for(const theme of ['market-light','market-dark','minimal-light','minimal-dark']){await page.setViewportSize({width:1440,height:1000});await go('/?_theme='+theme);assert.deepEqual(await names('popular_products'),['Feed a','Feed c']);await page.setViewportSize({width:390,height:844});assert(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth+1),'mobile width '+theme);}
  await page.locator('[data-product-feed="latest_products"]').screenshot({path:'protected/runtime/home-widgets-mobile.png'});
  query('Yii::app()->db->createCommand()->delete("page_visits");Yii::app()->siteCache->invalidateAll();');await go('/?_theme=market-light');assert.equal(await page.locator('[data-product-feed="popular_products"] .product-card').count(),0);assert.match(await page.locator('[data-product-feed="popular_products"]').textContent(),/هنوز بازدیدی/);
  assert.deepEqual(errors,[]);console.log('PASS feed cache invalidation, invalid category rollback, empty visits state, four themes and mobile rendering');
 }finally{await browser.close();}
})().catch(e=>{console.error(e);process.exitCode=1;});
