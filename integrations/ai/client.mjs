import {readFile} from 'node:fs/promises';
import {resolve} from 'node:path';

/** Shared direct API client. Credentials stay in environment or an ignored file. */
export async function createClient(overrides={}) {
  let config={};
  const file=overrides.configFile||process.env.BAZAARCHE_TOKEN_FILE||resolve('protected/config/ai-client.json');
  try {config=JSON.parse(await readFile(file,'utf8'));}catch(e){if(e.code!=='ENOENT')throw Error('Cannot read private API configuration');}
  const base=new URL(overrides.url||process.env.BAZAARCHE_API_URL||config.url||'http://127.0.0.1:8080/api/v1/');
  const token=overrides.token||process.env.BAZAARCHE_API_TOKEN||config.token;
  if(!token)throw Error('Set BAZAARCHE_API_TOKEN or BAZAARCHE_TOKEN_FILE');
  if(base.username||base.password||base.search||base.hash)throw Error('API URL must not contain credentials, query or fragment');
  if(base.protocol!=='https:'&&!(base.protocol==='http:'&&['localhost','127.0.0.1','[::1]'].includes(base.hostname)))throw Error('HTTPS required except loopback development');
  if(!base.pathname.endsWith('/'))base.pathname+='/';
  async function request(path,body,key){
    const response=await fetch(new URL(path,base),{method:body?'POST':'GET',redirect:'error',signal:AbortSignal.timeout(30000),
      headers:{Authorization:'Bearer '+token,Accept:'application/json',...(body?{'Content-Type':'application/json'}:{}),...(key?{'Idempotency-Key':key}:{})},...(body?{body:JSON.stringify(body)}:{})});
    const contentType=response.headers.get('content-type')||'';if(!contentType.includes('application/json'))throw Error('API returned a non-JSON response ('+response.status+')');
    const json=await response.json();if(!response.ok||json.ok===false){const error=Error(json.error?.message||'API error '+response.status);error.status=response.status;throw error;}
    return json.data??json;
  }
  return {schema:()=>request('schema'),whoami:()=>request('whoami'),openapi:()=>request('openapi'),call:(operation,args={},key)=>request('call',{operation,arguments:args},key)};
}
