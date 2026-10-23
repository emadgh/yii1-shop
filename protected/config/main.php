<?php
date_default_timezone_set('Asia/Tehran');
$root=dirname(__DIR__,2);
require_once $root.'/vendor/autoload.php';
$dsn=getenv('SHOP_DSN') ?: 'sqlite:'.$root.'/protected/runtime/store.sqlite';
$config=array(
  'basePath'=>dirname(__DIR__),
  'name'=>'بازارچه | فروشگاه اینترنتی',
  'language'=>'fa_ir',
  'sourceLanguage'=>'en_us',
  'defaultController'=>'site',
  'import'=>array('application.models.*','application.components.*','application.repositories.*','application.queries.*','application.services.*','application.forms.*','application.widgets.*'),
  'components'=>array(
    'assetManager'=>array('basePath'=>$root.'/public/assets'),
    'siteCache'=>array('class'=>'application.components.SiteCache'),
    'db'=>array(
      'connectionString'=>$dsn,
      'username'=>getenv('SHOP_DB_USER') ?: '',
      'password'=>getenv('SHOP_DB_PASSWORD') ?: '',
      'charset'=>'utf8mb4',
      'emulatePrepare'=>false,
      'enableParamLogging'=>false,
      'initSQLs'=>strpos($dsn,'sqlite:')===0 ? array('PRAGMA foreign_keys = ON','PRAGMA busy_timeout = 5000') : array(),
    ),
    'user'=>array('allowAutoLogin'=>false,'loginUrl'=>array('site/login')),
    'request'=>array('class'=>'application.components.ApiHttpRequest','enableCsrfValidation'=>true,'enableCookieValidation'=>true,'csrfCookie'=>array('httpOnly'=>true,'sameSite'=>'Lax')),
    'session'=>array('cookieParams'=>array('httponly'=>true,'samesite'=>'Lax')),
    'urlManager'=>array('class'=>'application.components.StoreUrlManager',
       'urlFormat'=>'path','showScriptName'=>false,
       'rules'=>array(
         'api/v1/<action:call|schema|openapi|whoami>'=>'api/<action>',''=> 'site/index','sitemap.xml'=>'seo/sitemap','robots.txt'=>'seo/robots',
         'shop'=>'shop/index',
         'collection/<slug:[a-z0-9-]+>'=>'collection/view',
         'product/<slug:[a-z0-9-]+>'=>'shop/view',
         'category/<slug:[a-z0-9-]+>'=>'shop/category',
         'blog'=>'blog/index',
         'catalogs'=>'catalog/index',
         'blog/<slug:[a-z0-9-]+>'=>'blog/view',
         'cart'=>'cart/index',
         'checkout'=>'cart/checkout',
         'login'=>'site/login',
         'admin'=>'adminDashboard/index',
         '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
       )
    ),
    'errorHandler'=>array('class'=>'application.components.StoreErrorHandler'),
    'cart'=>array('class'=>'application.components.StoreCart'),
  ),
  'params'=>array('currency'=>'تومان','uploadsDir'=>$root.'/public/uploads','uploadsUrl'=>'/uploads'),
);
if(is_file(__DIR__.'/local.php')) $config=CMap::mergeArray($config,require __DIR__.'/local.php');
if(strpos($config['components']['db']['connectionString'],'mysql:')===0)$config['components']['db']['initSQLs']=array();
return $config;
