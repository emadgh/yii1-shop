import {readFile} from 'node:fs/promises';
import {basename} from 'node:path';
import {createClient} from './client.mjs';
const [command,...args]=process.argv.slice(2);
try{
  const api=await createClient();let result;
  if(command==='schema')result=await api.schema();
  else if(command==='whoami')result=await api.whoami();
  else if(command==='openapi')result=await api.openapi();
  else if(command==='call'){
    const [operation,file,key]=args;if(!operation||!file)throw Error('call OPERATION ARGUMENTS_JSON_FILE [IDEMPOTENCY_KEY]');
    result=await api.call(operation,JSON.parse(await readFile(file,'utf8')),key);
  }else if(command==='upload'){
    const [file,key,scope='general']=args;if(!file||!key)throw Error('upload LOCAL_FILE IDEMPOTENCY_KEY [general|catalogs]');
    const bytes=await readFile(file);if(bytes.length>10*1024*1024)throw Error('File exceeds 10 MB');
    result=await api.call('media_upload',{filename:basename(file),content_base64:bytes.toString('base64'),scope},key);
  }else throw Error('Commands: schema | whoami | openapi | call OPERATION JSON_FILE [KEY] | upload FILE KEY [SCOPE]');
  process.stdout.write(JSON.stringify(result,null,2)+'\n');
}catch(error){console.error(JSON.stringify({status:error.status||null,error:error.message}));process.exitCode=1;}
