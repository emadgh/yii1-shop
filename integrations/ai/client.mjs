import {readFile} from 'node:fs/promises';
import {resolve} from 'node:path';

/** Shared direct API client. Credentials stay in environment or an ignored file. */
export async function createClient(overrides={}) {
  let config={};
  const explicitFile=overrides.configFile||process.env.BAZAARCHE_TOKEN_FILE;
  const environment=!explicitFile&&(process.env.BAZAARCHE_API_URL||process.env.BAZAARCHE_API_TOKEN);
  const file=explicitFile||resolve('protected/config/ai-client.json');
  if(!environment&&!(overrides.url&&overrides.token)){
    try {config=JSON.parse((await readFile(file,'utf8')).replace(/^\uFEFF/,''));}
    catch{throw Error('Cannot read private API configuration. Check BAZAARCHE_TOKEN_FILE.');}
  }
  const url=overrides.url||(environment?process.env.BAZAARCHE_API_URL:config.url);
  const token=overrides.token||(environment?process.env.BAZAARCHE_API_TOKEN:config.token);
  if(!url)throw Error('Set an explicit API URL together with its token; localhost is never selected automatically');
  let base;
  try {base=new URL(url);}catch{throw Error('Invalid API URL. Use a plain https:// URL, without Markdown link formatting.');}
  if(!token)throw Error('Set BAZAARCHE_API_TOKEN or BAZAARCHE_TOKEN_FILE');
  if(base.username||base.password||base.search||base.hash)throw Error('API URL must not contain credentials, query or fragment');
  if(base.protocol!=='https:'&&!(base.protocol==='http:'&&['localhost','127.0.0.1','[::1]'].includes(base.hostname)))throw Error('HTTPS required except loopback development');
  if(!base.pathname.endsWith('/'))base.pathname+='/';
  async function request(path,body,key){
    const response=await fetch(new URL(path,base),{method:body?'POST':'GET',redirect:'error',signal:AbortSignal.timeout(30000),
      headers:{Authorization:'Bearer '+token,Accept:'application/json',...(body?{'Content-Type':'application/json'}:{}),...(key?{'Idempotency-Key':key}:{})},...(body?{body:JSON.stringify(body)}:{})});
    const contentType=response.headers.get('content-type')||'';if(!contentType.includes('application/json'))throw Error('API at '+base.origin+' returned a non-JSON response ('+response.status+'). Check the API route and hosting browser/JavaScript challenges.');
    const json=await response.json();if(!response.ok||json.ok===false){const error=Error(json.error?.message||'API error '+response.status);error.status=response.status;throw error;}
    return json.data??json;
  }
  return {connection:()=>({api_url:base.href,configuration_source:explicitFile?'explicit_file':environment?'environment':'default_file',restart_required_after_configuration_change:true}),schema:()=>request('schema'),whoami:()=>request('whoami'),openapi:()=>request('openapi'),call:(operation,args={},key)=>request('call',{operation,arguments:args},key)};
}
