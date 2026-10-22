const {spawn}=require('node:child_process');
const fs=require('node:fs');const assert=require('node:assert/strict');const path=require('node:path');
const copy=path.resolve('protected/runtime/race-'+Date.now()+'.sqlite');fs.copyFileSync('protected/runtime/store.sqlite',copy);
const {php,phpArgs}=require('./test-runtime.cjs');const args=phpArgs.concat('tools/race-buyer.php');
function run(...values){return new Promise((resolve,reject)=>{const p=spawn(php,args.concat(values.map(String)),{env:{...process.env,SHOP_DSN:'sqlite:'+copy}});let out='';p.stdout.on('data',x=>out+=x);p.stderr.on('data',x=>process.stderr.write(x));p.on('error',reject);p.on('close',()=>{try{resolve(JSON.parse(out));}catch(e){reject(new Error(out));}});});}
(async()=>{try{
const setup=await run('setup');const [a,b]=await Promise.all([run(setup.ids[0]),run(setup.ids[0])]);assert.equal([a,b].filter(x=>x.ok).length,1,'only one purchase of last stock');let state=await run('inspect');assert.equal(+state.stock[0].stock,0);assert.equal(state.orders,setup.orders+1);console.log('PASS concurrent checkout cannot oversell last unit');
const [c,d]=await Promise.all([run(setup.ids[1],'RACEONLY'),run(setup.ids[1],'RACEONLY')]);assert.equal([c,d].filter(x=>x.ok).length,1,'single use coupon race');state=await run('inspect');assert.equal(state.coupon,1);assert.equal(+state.stock[1].stock,9);assert.equal(state.orders,setup.orders+2);console.log('PASS concurrent coupon limit; rejected checkout restores all writes');
}finally{fs.unlinkSync(copy);}})().catch(e=>{console.error(e);process.exitCode=1;});
