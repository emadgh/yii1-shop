import {cp,mkdir,readFile,writeFile,access} from 'node:fs/promises';
import {homedir} from 'node:os';
import {resolve,dirname,join} from 'node:path';
import {fileURLToPath} from 'node:url';
const root=resolve(dirname(fileURLToPath(import.meta.url)),'../..');
const target=join(process.env.CODEX_HOME||join(homedir(),'.codex'),'skills','bazaarche-publish');
let exists=false;try{await access(target);exists=true;}catch{}
if(exists&&!process.argv.includes('--update'))throw Error('Skill already exists; inspect it before using --update');
await mkdir(target,{recursive:true});
await cp(join(root,'.agents/skills/bazaarche-publish'),target,{recursive:true});
await mkdir(join(target,'scripts'),{recursive:true});
await cp(join(root,'integrations/ai/client.mjs'),join(target,'scripts/api-client.mjs'));
const cli=(await readFile(join(root,'integrations/ai/cli.mjs'),'utf8')).replace("from './client.mjs'","from './api-client.mjs'");
await writeFile(join(target,'scripts/client.mjs'),cli);
console.log('Installed Bazaarche publishing skill: '+target);
