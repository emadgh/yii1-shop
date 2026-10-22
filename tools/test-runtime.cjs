const path=require('node:path'),os=require('node:os'),fs=require('node:fs');
let playwright;try{playwright=require('playwright');}catch(e){const bundled=process.env.SHOP_PLAYWRIGHT_PATH||path.join(os.homedir(),'.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');playwright=require(bundled);}
const windowsChrome=path.join(process.env.ProgramFiles||'C:/Program Files','Google/Chrome/Application/chrome.exe');
const launchOptions={headless:true};if(process.env.SHOP_CHROME_BINARY)launchOptions.executablePath=process.env.SHOP_CHROME_BINARY;else if(process.platform==='win32'&&fs.existsSync(windowsChrome))launchOptions.executablePath=windowsChrome;
const localPhp='H:/wamp64/bin/php/php8.4.0/php.exe';const php=process.env.SHOP_PHP_BINARY||(process.platform==='win32'&&fs.existsSync(localPhp)?localPhp:'php');
let phpArgs=[];if(process.platform==='win32'&&/wamp64/i.test(php)){phpArgs=['-n','-d','extension_dir='+path.join(path.dirname(php),'ext')];for(const ext of ['pdo_sqlite','mbstring','fileinfo','gd','openssl'])phpArgs.push('-d','extension=php_'+ext+'.dll');}
module.exports={...playwright,launchOptions,php,phpArgs};
