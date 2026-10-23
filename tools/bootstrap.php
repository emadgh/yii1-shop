<?php
// Authenticated web upgrades reuse the running application's DB and user/session.
if(defined('BAZAARCHE_WEB_UPGRADE')&&BAZAARCHE_WEB_UPGRADE===true){
    if(class_exists('Yii',false)&&Yii::app() instanceof CWebApplication&&AccessService::isSuperAdmin())return;
    http_response_code(403);exit;
}
if (PHP_SAPI !== 'cli' && !defined('BAZAARCHE_INSTALL_CONFIG')) { http_response_code(403); exit; }
require_once dirname(__DIR__).'/vendor/yiisoft/yii/framework/yii.php';
$config=defined('BAZAARCHE_INSTALL_CONFIG')?BAZAARCHE_INSTALL_CONFIG:require dirname(__DIR__).'/protected/config/main.php';
unset($config['defaultController']);
$config['components']=array('db'=>$config['components']['db'],'siteCache'=>$config['components']['siteCache']);
// The installer reuses migrations in-process; no console command is executed.
if(defined('BAZAARCHE_INSTALL_CONFIG')&&empty($_SERVER['argv']))$_SERVER['argv']=array('web-installer');
Yii::createConsoleApplication($config);
Yii::app()->db->active=true;
