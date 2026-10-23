import assert from 'node:assert/strict';
import {mkdtemp,writeFile,rm} from 'node:fs/promises';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {createClient} from '../integrations/ai/client.mjs';

const directory=await mkdtemp(join(tmpdir(),'bazaarche-config-'));
const keys=['BAZAARCHE_TOKEN_FILE','BAZAARCHE_API_URL','BAZAARCHE_API_TOKEN'];
const previous=Object.fromEntries(keys.map(key=>[key,process.env[key]]));
const originalFetch=globalThis.fetch;
try{
  const file=join(directory,'client.json');
  await writeFile(file,'\uFEFF'+JSON.stringify({url:'https://shop.example/api/v1/',token:'file-secret'}));
  process.env.BAZAARCHE_TOKEN_FILE=file;
  process.env.BAZAARCHE_API_URL='http://127.0.0.1:8080/api/v1/';
  process.env.BAZAARCHE_API_TOKEN='stale-environment-secret';
  const api=await createClient();
  assert.equal(api.connection().api_url,'https://shop.example/api/v1/');
  assert.equal(api.connection().configuration_source,'explicit_file');
  assert(!JSON.stringify(api.connection()).includes('secret'));
  globalThis.fetch=async(url,options)=>{
    assert.equal(url.href,'https://shop.example/api/v1/whoami');
    assert.equal(options.headers.Authorization,'Bearer file-secret');
    assert.equal(options.redirect,'error');
    return Response.json({ok:true,data:{id:1}});
  };
  assert.deepEqual(await api.whoami(),{id:1});
  process.env.BAZAARCHE_TOKEN_FILE=join(directory,'missing.json');
  await assert.rejects(createClient(),/Cannot read private/);
  delete process.env.BAZAARCHE_TOKEN_FILE;
  assert.equal((await createClient()).connection().configuration_source,'environment');
  delete process.env.BAZAARCHE_API_URL;
  await assert.rejects(createClient(),/explicit API URL/);
  await assert.rejects(createClient({url:'[https://shop.example/](https://shop.example/)',token:'secret'}),/plain https/);
  await assert.rejects(createClient({url:'http://shop.example/api/v1/',token:'secret'}),/HTTPS required/);
  globalThis.fetch=async()=>new Response('<html>browser challenge</html>',{headers:{'Content-Type':'text/html'}});
  await assert.rejects(api.whoami(),/non-JSON.*browser\/JavaScript/);
  console.log('PASS MCP destination precedence, credential pairing, missing config, URL validation and safe diagnostics');
}finally{
  globalThis.fetch=originalFetch;
  for(const key of keys){if(previous[key]===undefined)delete process.env[key];else process.env[key]=previous[key];}
  await rm(directory,{recursive:true,force:true});
}
